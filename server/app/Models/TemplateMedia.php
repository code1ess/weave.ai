<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TemplateMedia extends Model
{
    use HasUuids;

    protected $table = 'template_media';

    protected $fillable = ['admin_user_id', 'disk', 'path', 'mime', 'size'];

    protected $hidden = ['path'];
}
