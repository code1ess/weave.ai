<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GenerationMode extends Model
{
    protected $primaryKey = 'kind';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['active', 'aspect_ratios', 'pause_message', 'version'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'aspect_ratios' => 'array', 'version' => 'integer'];
    }
}
