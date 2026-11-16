<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'task_id', 'subject', 'body', 'status', 'idempotency_key'];
}
