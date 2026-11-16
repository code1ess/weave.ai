<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Facades\Storage;

class InputFingerprint
{
    public function make(string $kind, string $prompt, ?string $assetId): string
    {
        $assetHash = '';
        if ($assetId) {
            $asset = Asset::findOrFail($assetId);
            $assetHash = $asset->sha256;
            if (! $assetHash) {
                $stream = Storage::disk($asset->disk)->readStream($asset->path);
                abort_unless(is_resource($stream), 503, __('无法读取参考图片。'));
                try {
                    $context = hash_init('sha256');
                    hash_update_stream($context, $stream);
                    $assetHash = hash_final($context);
                } finally {
                    fclose($stream);
                }
                $asset->update(['sha256' => $assetHash]);
            }
        }

        return hash('sha256', json_encode([$kind, mb_strtolower(trim(preg_replace('/\s+/u', ' ', $prompt))), $assetHash], JSON_THROW_ON_ERROR));
    }
}
