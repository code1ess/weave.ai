<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasUuids;

    protected $attributes = ['status' => 'active'];

    protected $fillable = ['user_id', 'path', 'mime', 'size', 'disk', 'sha256'];

    protected $hidden = ['path', 'sha256'];
}
