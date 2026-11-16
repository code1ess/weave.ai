<?php

namespace App\Http\Controllers;

use App\Models\CreativeTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TemplateController extends Controller
{
    private function query()
    {
        return DB::table('templates as t')->join('template_versions as v', fn ($join) => $join->on('t.id', '=', 'v.template_id')->on('t.current_version', '=', 'v.version'));
    }

    public function index(Request $request)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:100', 'category' => 'nullable|string|max:80', 'kind' => 'nullable|in:text_to_image,image_to_image,image_to_video', 'tag' => 'nullable|string|max:30']);
        $query = $this->query()->where('t.status', 'published');
        foreach (['category', 'kind'] as $key) {
            if (! empty($filters[$key])) {
                $query->where('v.'.$key, $filters[$key]);
            }
        }
        if (! empty($filters['tag'])) {
            $query->whereJsonContains('v.tags', $filters['tag']);
        }
        if (! empty($filters['search'])) {
            $query->where('v.title', 'like', '%'.$filters['search'].'%');
        }

        return Inertia::render('Templates', ['templates' => $query->select('t.id', 'v.title', 'v.description', 'v.category', 'v.kind', 'v.version', 'v.tags', 'v.cover_media_id')->orderBy('v.sort_order')->orderByDesc('t.created_at')->paginate(12)->withQueryString()->through(fn ($row) => $this->publicRecord($row)), 'filters' => $filters, 'categories' => $this->query()->where('t.status', 'published')->distinct()->orderBy('v.category')->pluck('v.category')]);
    }

    public function show(string $template)
    {
        $record = $this->query()->where('t.id', $template)->where('t.status', 'published')->select('t.id', 'v.title', 'v.description', 'v.kind', 'v.prompt', 'v.category', 'v.version', 'v.tags', 'v.cover_media_id')->first();
        abort_unless($record, 404);

        return Inertia::render('TemplateDetail', ['template' => $this->publicRecord($record)]);
    }

    public function adminIndex()
    {
        return Inertia::render('Admin/Templates', ['templates' => $this->query()->select('t.id', 't.status', 'v.version', 'v.title', 'v.description', 'v.kind', 'v.prompt', 'v.category', 'v.source_license', 'v.tags', 'v.cover_media_id', 'v.sort_order')->orderByDesc('t.created_at')->paginate(20)->through(function ($row) {
            $row->tags = json_decode($row->tags ?? '[]', true);

            return $row;
        })]);
    }

    public function save(Request $request, ?string $template = null)
    {
        $data = $request->validate(['title' => 'required|string|max:120', 'description' => 'required|string|max:2000', 'kind' => 'required|in:text_to_image,image_to_image,image_to_video', 'prompt' => 'required|string|max:4000', 'category' => 'required|string|max:80', 'source_license' => 'required|string|max:2000', 'reason' => 'required|string|max:1000', 'version' => 'nullable|integer|min:1', 'cover_media_id' => 'nullable|uuid|exists:template_media,id', 'tags' => 'nullable|array|max:12', 'tags.*' => 'required|string|max:30|distinct', 'sort_order' => 'nullable|integer|between:0,1000000']);
        DB::transaction(function () use ($request, $data, $template) {
            $record = $template ? CreativeTemplate::lockForUpdate()->findOrFail($template) : CreativeTemplate::create(['status' => 'draft', 'current_version' => 1]);
            if ($template && (int) ($data['version'] ?? 0) !== $record->current_version) {
                throw ValidationException::withMessages(['version' => __('模板已更新，请刷新后重试。')]);
            }
            $version = $template ? $record->current_version + 1 : 1;
            DB::table('template_versions')->insert([...collect($data)->only(['title', 'description', 'kind', 'prompt', 'category', 'source_license', 'cover_media_id'])->all(), 'tags' => json_encode($data['tags'] ?? [], JSON_THROW_ON_ERROR), 'sort_order' => $data['sort_order'] ?? 0, 'template_id' => $record->id, 'version' => $version, 'created_at' => now()]);
            $record->update(['status' => 'draft', 'current_version' => $version]);
            DB::table('admin_audit_logs')->insert(['admin_user_id' => $request->user('admin')->id, 'action' => 'template.saved', 'subject' => $record->id, 'reason' => $data['reason'], 'created_at' => now()]);
        });

        return redirect('/admin/templates');
    }

    private function publicRecord(object $row): object
    {
        $row->tags = json_decode($row->tags ?? '[]', true);
        $row->cover_url = $row->cover_media_id ? '/templates/'.$row->id.'/cover' : null;
        unset($row->cover_media_id);

        return $row;
    }

    public function status(Request $request, string $template)
    {
        $data = $request->validate(['status' => 'required|in:pending_review,published,archived', 'version' => 'required|integer|min:1', 'reason' => 'required|string|max:1000']);
        DB::transaction(function () use ($request, $data, $template) {
            $record = CreativeTemplate::lockForUpdate()->findOrFail($template);
            if ($record->current_version !== (int) $data['version']) {
                throw ValidationException::withMessages(['version' => __('模板已更新，请刷新后重试。')]);
            }
            $allowed = ['draft' => ['pending_review'], 'pending_review' => ['published', 'archived'], 'published' => ['archived'], 'archived' => ['pending_review']];
            if (! in_array($data['status'], $allowed[$record->status], true)) {
                throw ValidationException::withMessages(['status' => __('无效状态变更，请先提交审核。')]);
            }
            $record->update(['status' => $data['status']]);
            DB::table('admin_audit_logs')->insert(['admin_user_id' => $request->user('admin')->id, 'action' => 'template.'.$data['status'], 'subject' => $record->id, 'reason' => $data['reason'], 'created_at' => now()]);
        });

        return redirect('/admin/templates');
    }
}
