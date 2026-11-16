<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminUser extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password', 'role', 'active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'active' => 'boolean'];
    }

    public function canAccess(string $permission): bool
    {
        return $this->active && in_array($permission, config('admin.roles.'.$this->role, []), true);
    }
}
