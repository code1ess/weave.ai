<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\CreativeTemplate;
use App\Models\GenerationMode;
use App\Models\GenerationTask;
use App\Models\User;
use App\Services\InputFingerprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Studio', ['selectedKind' => $request->validate(['kind' => 'nullable|in:text_to_image,image_to_image,image_to_video'])['kind'] ?? null, 'modes' => GenerationMode::orderBy('kind')->get(['kind', 'active', 'aspect_ratios', 'pause_message', 'version']), 'pendingLimit' => config('generation.pending_limit'), 'selectedAsset' => $this->selectedAsset($request), 'selectedTemplate' => $this->selectedTemplate($request), 'tasks' => GenerationTask::where('user_id', $request->user()->id)->latest()->paginate(12)]);
    }

    public function store(Request $request, InputFingerprint $fingerprints)
    {
        $data = $request->validate(['kind' => 'required|in:text_to_image,image_to_image,image_to_video', 'prompt' => 'required|string|max:4000', 'idempotency_key' => 'required|uuid', 'asset_id' => ['nullable', 'required_unless:kind,text_to_image', 'uuid', Rule::exists('assets', 'id')->where('user_id', $request->user()->id)->where('status', 'active')]]);
        $options = $request->validate(['parameters' => 'nullable|array:aspect_ratio,duration_seconds', 'parameters.aspect_ratio' => 'sometimes|required|in:1:1,16:9,9:16,4:3,3:4', 'parameters.duration_seconds' => 'nullable|prohibited_unless:kind,image_to_video|integer|in:5', 'mode_version' => 'nullable|integer|min:1']);
        $parameters = ['aspect_ratio' => $options['parameters']['aspect_ratio'] ?? '1:1', 'duration_seconds' => $data['kind'] === 'image_to_video' ? 5 : null];
        $request->validate(['template_id' => 'nullable|uuid', 'template_version' => 'nullable|required_with:template_id|integer|min:1']);
        $identity = ['user_id' => $request->user()->id, 'idempotency_key' => $data['idempotency_key']];
        $input = ['kind' => $data['kind'], 'prompt' => $data['prompt'], 'asset_id' => $data['asset_id'] ?? null, 'template_id' => $request->input('template_id'), 'template_version' => $request->filled('template_id') ? (int) $request->input('template_version') : null];
        $hash = $fingerprints->make($input['kind'], $input['prompt'], $input['asset_id']);
        DB::transaction(function () use ($identity, $input, $hash, $parameters, $options) {
            User::whereKey($identity['user_id'])->lockForUpdate()->firstOrFail();
            // Lock templates against concurrent publication changes during new submissions.
            $template = $input['template_id'] ? CreativeTemplate::lockForUpdate()->find($input['template_id']) : null;
            $task = GenerationTask::where($identity)->first();
            if (! $task) {
                $mode = GenerationMode::lockForUpdate()->findOrFail($input['kind']);
                if (! $mode->active) {
                    throw ValidationException::withMessages(['kind' => $mode->pause_message ?: __('此模式暂时停止接单。')]);
                }
                if (isset($options['mode_version']) && (int) $options['mode_version'] !== $mode->version) {
                    throw ValidationException::withMessages(['mode_version' => __('创作配置已更新，请刷新页面。')]);
                }
                if (! in_array($parameters['aspect_ratio'], $mode->aspect_ratios, true)) {
                    throw ValidationException::withMessages(['parameters.aspect_ratio' => __('当前模式不接受此比例，请重新选择。')]);
                }
                if (GenerationTask::where('user_id', $identity['user_id'])->whereIn('status', ['pending', 'processing'])->count() >= config('generation.pending_limit')) {
                    throw ValidationException::withMessages(['task' => __('待处理任务已达上限，请取消部分任务后重试。')]);
                }

                if ($input['asset_id'] && ! Asset::whereKey($input['asset_id'])->where('user_id', $identity['user_id'])->where('status', 'active')->exists()) {
                    throw ValidationException::withMessages(['asset_id' => __('参考图片已不可用，请重新选择。')]);
                }
                if (GenerationTask::where('user_id', $identity['user_id'])->where('input_hash', $hash)->where('moderation_status', 'rejected')->exists()) {
                    throw ValidationException::withMessages(['prompt' => __('相同输入已被审核拒绝，请通过工单申请复核。')]);
                }
                if ($input['template_id'] && (! $template || $template->status !== 'published' || $template->current_version !== $input['template_version'])) {
                    throw ValidationException::withMessages(['template_id' => __('模板已更新或下架，请重新选择。')]);
                }
                if ($template) {
                    $version = DB::table('template_versions')->where('template_id', $template->id)->where('version', $input['template_version'])->first();
                    if ($version->kind !== $input['kind']) {
                        throw ValidationException::withMessages(['kind' => __('任务模式必须与模板一致。')]);
                    }
                }
                $task = GenerationTask::firstOrCreate($identity, [...$input, 'status' => 'pending', 'input_hash' => $hash, 'parameters' => $parameters, 'mode_version' => $mode->version]);
            }
            $storedParameters = $task->parameters ?? ['aspect_ratio' => '1:1', 'duration_seconds' => $task->kind === 'image_to_video' ? 5 : null];
            if (['aspect_ratio' => $storedParameters['aspect_ratio'] ?? '1:1', 'duration_seconds' => $storedParameters['duration_seconds'] ?? null] !== $parameters) {
                throw ValidationException::withMessages(['idempotency_key' => __('此提交标识已用于其他参数，请重新提交。')]);
            }
            foreach ($input as $key => $value) {
                if ((string) $task->$key !== (string) $value) {
                    throw ValidationException::withMessages(['idempotency_key' => __('此提交标识已用于其他任务，请重新提交。')]);
                }
            }
        });

        return to_route('studio');
    }

    public function history(Request $request)
    {
        $filters = $request->validate(['kind' => 'nullable|in:text_to_image,image_to_image,image_to_video', 'status' => 'nullable|in:pending,cancelled,rejected,processing,succeeded,failed']);
        $query = GenerationTask::where('user_id', $request->user()->id);
        foreach (['kind', 'status'] as $key) {
            if (! empty($filters[$key])) {
                $query->where($key, $filters[$key]);
            }
        }

        return Inertia::render('Works', ['tasks' => $query->latest()->paginate(18)->withQueryString(), 'filters' => $filters]);
    }

    public function detail(Request $request, string $task)
    {
        $record = GenerationTask::where('user_id', $request->user()->id)->findOrFail($task);
        $template = $record->template_id ? DB::table('template_versions')->where('template_id', $record->template_id)->where('version', $record->template_version)->first(['title', 'version']) : null;

        return Inertia::render('WorkDetail', ['task' => $record, 'template' => $template, 'events' => DB::table('task_events')->where('task_id', $task)->orderByDesc('id')->limit(50)->get(), 'review' => DB::table('moderation_reviews')->where('task_id', $task)->first(['decision', 'reason'])]);
    }

    private function selectedAsset(Request $request): ?string
    {
        $request->validate(['asset' => 'nullable|uuid']);
        if (! $request->filled('asset')) {
            return null;
        }

        return Asset::where('user_id', $request->user()->id)->where('status', 'active')->findOrFail($request->input('asset'))->id;
    }

    private function selectedTemplate(Request $request)
    {
        $request->validate(['template' => 'nullable|uuid']);
        if (! $request->filled('template')) {
            return null;
        }
        $template = CreativeTemplate::where('status', 'published')->findOrFail($request->input('template'));

        return DB::table('template_versions')->where('template_id', $template->id)->where('version', $template->current_version)->select('template_id', 'version', 'title', 'kind', 'prompt')->first();
    }

    public function cancel(Request $request, string $task)
    {
        DB::transaction(function () use ($request, $task) {
            $record = GenerationTask::where('user_id', $request->user()->id)->lockForUpdate()->findOrFail($task);
            if ($record->status === 'cancelled') {
                return;
            }
            if ($record->status !== 'pending') {
                throw ValidationException::withMessages(['task' => __('仅等待处理的任务可以取消。')]);
            }
            $record->update(['status' => 'cancelled']);
            DB::table('task_events')->insert(['task_id' => $task, 'type' => 'user.cancelled', 'created_at' => now()]);
        });

        return redirect('/studio');
    }

    public function show(Request $request, string $task)
    {
        return response()->json(GenerationTask::where('user_id', $request->user()->id)->findOrFail($task));
    }
}
