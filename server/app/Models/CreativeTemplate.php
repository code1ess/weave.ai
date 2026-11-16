<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CreativeTemplate extends Model
{
    use HasUuids;

    protected $table = 'templates';

    protected $fillable = ['status', 'current_version'];
}
