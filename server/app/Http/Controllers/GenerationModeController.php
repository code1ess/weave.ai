<?php

namespace App\Http\Controllers;

use App\Models\GenerationMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class GenerationModeController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/GenerationModes', ['modes' => GenerationMode::orderBy('kind')->get(), 'pendingLimit' => config('generation.pending_limit')]);
    }

    public function update(Request $request, string $kind)
    {
        $data = $request->validate(['active' => 'required|boolean', 'aspect_ratios' => 'required|array|min:1|max:5', 'aspect_ratios.*' => 'required|in:1:1,16:9,9:16,4:3,3:4|distinct', 'pause_message' => 'nullable|string|max:200', 'version' => 'required|integer|min:1', 'reason' => 'required|string|max:1000']);
        DB::transaction(function () use ($request, $kind, $data) {
            $mode = GenerationMode::lockForUpdate()->findOrFail($kind);
            if ($mode->version !== (int) $data['version']) {
                throw ValidationException::withMessages(['version' => __('配置已更新，请刷新后重试。')]);
            }
            $mode->update(['active' => (bool) $data['active'], 'aspect_ratios' => $data['aspect_ratios'], 'pause_message' => $data['pause_message'] ?? null, 'version' => $mode->version + 1]);
            DB::table('admin_audit_logs')->insert(['admin_user_id' => $request->user('admin')->id, 'action' => 'generation.mode_updated', 'subject' => $kind, 'reason' => $data['reason'], 'created_at' => now()]);
        });

        return redirect('/admin/generation-modes');
    }
}
