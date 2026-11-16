<?php

namespace App\Http\Controllers;

use App\Models\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SiteContentController extends Controller
{
    private function published(string $kind)
    {
        return DB::table('site_contents as c')->join('site_content_versions as v', fn ($join) => $join->on('c.id', '=', 'v.content_id')->on('c.published_version', '=', 'v.version'))->where('c.kind', $kind)->select('c.id', 'c.slug', 'c.published_at', 'v.title', 'v.body');
    }

    public function home()
    {
        return Inertia::render('Home', ['featuredTemplates' => DB::table('templates as t')->join('template_versions as v', fn ($join) => $join->on('t.id', '=', 'v.template_id')->on('t.current_version', '=', 'v.version'))->where('t.status', 'published')->orderBy('v.sort_order')->limit(8)->get(['t.id', 'v.title', 'v.kind', 'v.category', 'v.cover_media_id'])->map(function ($row) {
            $row->cover_url = $row->cover_media_id ? '/templates/'.$row->id.'/cover' : null;
            unset($row->cover_media_id);

            return $row;
        }), 'announcements' => $this->published('announcement')->orderByDesc('c.published_at')->limit(3)->get()]);
    }

    public function help()
    {
        return Inertia::render('Help', ['articles' => $this->published('help')->orderByDesc('c.published_at')->paginate(15)]);
    }

    public function article(string $slug)
    {
        $article = $this->published('help')->where('c.slug', $slug)->first();
        abort_unless($article, 404);

        return Inertia::render('HelpDetail', ['article' => $article]);
    }

    public function adminIndex()
    {
        return Inertia::render('Admin/Content', ['contents' => DB::table('site_contents as c')->join('site_content_versions as v', fn ($join) => $join->on('c.id', '=', 'v.content_id')->on('c.current_version', '=', 'v.version'))->select('c.id', 'c.slug', 'c.kind', 'c.current_version', 'c.published_version', 'v.title', 'v.body')->orderByDesc('c.updated_at')->paginate(20)]);
    }

    public function save(Request $request, ?string $content = null)
    {
        $data = $request->validate(['slug' => $content ? 'prohibited' : ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:site_contents'], 'kind' => $content ? 'prohibited' : 'required|in:help,announcement', 'title' => 'required|string|max:160', 'body' => 'required|string|max:20000', 'version' => 'nullable|integer|min:1', 'reason' => 'required|string|max:1000']);
        DB::transaction(function () use ($request, $content, $data) {
            $record = $content ? SiteContent::lockForUpdate()->findOrFail($content) : SiteContent::create(['slug' => $data['slug'], 'kind' => $data['kind'], 'current_version' => 1]);
            if ($content && $record->current_version !== (int) ($data['version'] ?? 0)) {
                throw ValidationException::withMessages(['version' => __('内容已被更新，请刷新后重试。')]);
            }
            $version = $content ? $record->current_version + 1 : 1;
            DB::table('site_content_versions')->insert(['content_id' => $record->id, 'version' => $version, 'title' => $data['title'], 'body' => $data['body'], 'created_at' => now()]);
            $record->update(['current_version' => $version]);
            $this->audit($request, 'content.saved', $record->id, $data['reason']);
        });

        return redirect('/admin/content');
    }

    public function publish(Request $request, string $content)
    {
        return $this->publication($request, $content, true);
    }

    public function withdraw(Request $request, string $content)
    {
        return $this->publication($request, $content, false);
    }

    private function publication(Request $request, string $content, bool $publish)
    {
        $data = $request->validate(['version' => 'required|integer|min:1', 'reason' => 'required|string|max:1000']);
        DB::transaction(function () use ($request, $content, $publish, $data) {
            $record = SiteContent::lockForUpdate()->findOrFail($content);
            if ($record->current_version !== (int) $data['version']) {
                throw ValidationException::withMessages(['version' => __('内容已被更新，请刷新后重试。')]);
            }
            $version = $publish ? $record->current_version : null;
            if ($record->published_version === $version) {
                return;
            }
            $record->update(['published_version' => $version, 'published_at' => $publish ? now() : null]);
            $this->audit($request, $publish ? 'content.published' : 'content.withdrawn', $content, $data['reason']);
        });

        return redirect('/admin/content');
    }

    private function audit(Request $request, string $action, string $id, string $reason): void
    {
        DB::table('admin_audit_logs')->insert(['admin_user_id' => $request->user('admin')->id, 'action' => $action, 'subject' => $id, 'reason' => $reason, 'created_at' => now()]);
    }
}
