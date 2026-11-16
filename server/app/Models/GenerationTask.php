<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class GenerationTask extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['parameters' => 'array', 'mode_version' => 'integer'];
    }

    protected $hidden = ['input_hash'];

    protected $fillable = ['user_id', 'idempotency_key', 'kind', 'prompt', 'status', 'asset_id', 'template_id', 'template_version', 'input_hash', 'parameters', 'mode_version'];
}
