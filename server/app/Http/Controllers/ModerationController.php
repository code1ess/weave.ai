<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\GenerationTask;
use App\Models\User;
use App\Services\InputFingerprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ModerationController extends Controller
{
    public function show(Request $request, string $task)
    {
        $record = GenerationTask::findOrFail($task);
        $sensitive = $request->user('admin')->canAccess('tasks.inputs');
        if ($sensitive) {
            $this->audit($request, 'task.input_viewed', $task);
        }

        return Inertia::render('Admin/TaskDetail', ['task' => $record->only(...($sensitive ? ['id', 'user_id', 'kind', 'status', 'moderation_status', 'prompt', 'asset_id', 'template_id', 'template_version', 'parameters', 'mode_version', 'created_at'] : ['id', 'user_id', 'kind', 'status', 'moderation_status', 'created_at'])), 'review' => DB::table('moderation_reviews')->where('task_id', $task)->select('decision', 'reason', 'created_at')->first(), 'events' => DB::table('task_events')->where('task_id', $task)->orderByDesc('id')->limit(50)->get()]);
    }

    public function input(Request $request, string $task)
    {
        $record = GenerationTask::findOrFail($task);
        $asset = Asset::findOrFail($record->asset_id);
        $this->audit($request, 'task.asset_viewed', $task);
        abort_unless(Storage::disk($asset->disk)->exists($asset->path), 404);
        if ($asset->disk === 'r2') {
            return redirect()->away(Storage::disk('r2')->url($asset->path))->withHeaders(['Cache-Control' => 'no-store, private']);
        }

        return Storage::disk($asset->disk)->response($asset->path, null, ['Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff', 'Content-Type' => $asset->mime]);
    }

    public function review(Request $request, string $task, InputFingerprint $fingerprints)
    {
        $data = $request->validate(['decision' => 'required|in:approved,rejected', 'reason' => 'required|string|max:1000', 'idempotency_key' => 'required|uuid']);
        $initial = GenerationTask::findOrFail($task);
        $hash = $fingerprints->make($initial->kind, $initial->prompt, $initial->asset_id);
        DB::transaction(function () use ($request, $task, $data, $initial, $hash) {
            User::whereKey($initial->user_id)->lockForUpdate()->firstOrFail();
            $record = GenerationTask::lockForUpdate()->findOrFail($task);
            $existing = DB::table('moderation_reviews')->where('task_id', $task)->first();
            if ($existing) {
                if ($existing->idempotency_key !== $data['idempotency_key'] || $existing->decision !== $data['decision'] || $existing->reason !== $data['reason'] || (int) $existing->admin_user_id !== $request->user('admin')->id) {
                    throw ValidationException::withMessages(['decision' => __('任务已有审核结论，不能覆盖。')]);
                }

                return;
            }
            if ($record->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => __('仅等待处理的任务可以审核。')]);
            }
            $record->forceFill(['input_hash' => $hash, 'moderation_status' => $data['decision'], 'status' => $data['decision'] === 'rejected' ? 'rejected' : 'pending'])->save();
            DB::table('moderation_reviews')->insert([...$data, 'task_id' => $task, 'admin_user_id' => $request->user('admin')->id, 'created_at' => now()]);
            DB::table('task_events')->insert(['task_id' => $task, 'type' => 'moderation.'.$data['decision'], 'created_at' => now()]);
            $this->audit($request, 'task.reviewed', $task, $data['reason']);
        });

        return redirect('/admin/tasks/'.$task);
    }

    private function audit(Request $request, string $action, string $task, ?string $reason = null): void
    {
        DB::table('admin_audit_logs')->insert(['admin_user_id' => $request->user('admin')->id, 'action' => $action, 'subject' => $task, 'reason' => $reason, 'created_at' => now()]);
    }
}
