<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SiteContent extends Model
{
    use HasUuids;

    protected $fillable = ['slug', 'kind', 'current_version', 'published_version', 'published_at'];

    protected function casts(): array
    {
        return ['current_version' => 'integer', 'published_version' => 'integer'];
    }
}
