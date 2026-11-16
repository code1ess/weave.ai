<?php

namespace App\Http\Controllers;

use App\Models\TemplateMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TemplateMediaController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['file' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:10240|dimensions:max_width=8192,max_height=8192']);
        $disk = config('assets.disk');
        abort_if($disk === 'r2' && ! config('filesystems.disks.r2.url'), 503, __('资产公开域名尚未配置。'));
        $file = $request->file('file');
        $path = $file->storeAs('assets', Str::uuid().'.'.$file->extension(), $disk);
        abort_unless($path, 503, __('封面保存失败，请重试。'));
        try {
            $media = DB::transaction(function () use ($request, $disk, $file, $path) {
                $media = TemplateMedia::create(['admin_user_id' => $request->user('admin')->id, 'disk' => $disk, 'path' => $path, 'mime' => $file->getMimeType(), 'size' => $file->getSize()]);
                DB::table('admin_audit_logs')->insert(['admin_user_id' => $request->user('admin')->id, 'action' => 'template.media_uploaded', 'subject' => $media->id, 'created_at' => now()]);

                return $media;
            });
        } catch (\Throwable $error) {
            Storage::disk($disk)->delete($path);
            throw $error;
        }

        return response()->json(['id' => $media->id], 201);
    }

    public function preview(string $media)
    {
        return $this->respond(TemplateMedia::findOrFail($media));
    }

    public function cover(string $template)
    {
        $record = DB::table('templates as t')->join('template_versions as v', fn ($join) => $join->on('t.id', '=', 'v.template_id')->on('t.current_version', '=', 'v.version'))->where('t.id', $template)->where('t.status', 'published')->first(['v.cover_media_id']);
        abort_unless($record?->cover_media_id, 404);

        return $this->respond(TemplateMedia::findOrFail($record->cover_media_id));
    }

    private function respond(TemplateMedia $media)
    {
        abort_unless(Storage::disk($media->disk)->exists($media->path), 404);
        if ($media->disk === 'r2') {
            return redirect()->away(Storage::disk('r2')->url($media->path))->withHeaders(['Cache-Control' => 'no-store']);
        }

        return Storage::disk($media->disk)->response($media->path, null, ['Content-Type' => $media->mime, 'Cache-Control' => 'no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
