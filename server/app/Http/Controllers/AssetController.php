<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\GenerationTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AssetController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['file' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:10240|dimensions:max_width=8192,max_height=8192']);
        $file = $request->file('file');
        $disk = config('assets.disk');
        abort_if($disk === 'r2' && ! config('filesystems.disks.r2.url'), 503, __('资产公开域名尚未配置。'));
        $path = $file->storeAs('assets', Str::uuid().'.'.$file->extension(), $disk);
        abort_unless($path, 503, __('文件保存失败，请重试。'));
        try {
            $asset = Asset::create(['user_id' => $request->user()->id, 'path' => $path, 'mime' => $file->getMimeType(), 'size' => $file->getSize(), 'disk' => $disk, 'sha256' => hash_file('sha256', $file->getRealPath())]);
        } catch (\Throwable $error) {
            Storage::disk($disk)->delete($path);
            throw $error;
        }

        return response()->json(['id' => $asset->id], 201);
    }

    public function index(Request $request)
    {
        return Inertia::render('Assets', ['assets' => Asset::where('user_id', $request->user()->id)->whereIn('status', ['active', 'deleting'])->select('id', 'status', 'size', 'mime', 'created_at')->selectSub(GenerationTask::selectRaw('count(*)')->whereColumn('asset_id', 'assets.id'), 'task_count')->latest()->paginate(18)]);
    }

    public function destroy(Request $request, string $asset)
    {
        $record = DB::transaction(function () use ($request, $asset) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $record = Asset::where('user_id', $request->user()->id)->lockForUpdate()->findOrFail($asset);
            if ($record->status === 'deleted') {
                return $record;
            }
            if (GenerationTask::where('asset_id', $asset)->exists()) {
                throw ValidationException::withMessages(['asset' => __('图片已被任务引用，暂不能删除。')]);
            }
            $record->forceFill(['status' => 'deleting'])->save();

            return $record;
        });
        if ($record->status === 'deleted') {
            return redirect('/assets');
        }
        try {
            if (! Storage::disk($record->disk)->delete($record->path)) {
                throw new \RuntimeException('Storage deletion failed');
            }
        } catch (\Throwable $error) {
            throw ValidationException::withMessages(['asset' => __('文件删除暂未完成，请重试。')]);
        }
        $record->forceFill(['status' => 'deleted', 'deleted_at' => now()])->save();

        return redirect('/assets');
    }

    public function show(Request $request, string $asset)
    {
        $record = Asset::where('user_id', $request->user()->id)->where('status', 'active')->findOrFail($asset);
        abort_unless(Storage::disk($record->disk)->exists($record->path), 404);

        if ($record->disk === 'r2') {
            return redirect()->away(Storage::disk('r2')->url($record->path))->withHeaders(['Cache-Control' => 'no-store, private']);
        }

        return Storage::disk($record->disk)->response($record->path, null, ['Content-Type' => $record->mime, 'Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff']);
    }
}
