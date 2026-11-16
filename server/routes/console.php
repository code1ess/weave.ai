<?php

use App\Models\AdminUser;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

Artisan::command('admin:create {email} {--name=Administrator} {--role=superadmin}', function () {
    $data = ['email' => $this->argument('email'), 'name' => $this->option('name'), 'role' => $this->option('role'), 'password' => $this->secret('Password (minimum 12 characters)')];
    $validator = Validator::make($data, ['email' => 'required|email|max:255|unique:admin_users', 'name' => 'required|string|max:100', 'role' => ['required', Rule::in(array_keys(config('admin.roles')))], 'password' => 'required|string|min:12']);
    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }
    AdminUser::create([...$data, 'active' => true]);
    $this->info('Administrator created.');

    return 0;
})->purpose('Create an administrator with an interactively entered password');

Artisan::command('app:check-services', function () {
    $ok = true;
    try {
        DB::select('SELECT 1 AS connection_check');
        $this->info('Database: OK');
    } catch (Throwable $error) {
        $this->error('Database: FAILED (check server-side connection configuration)');
        $ok = false;
    }
    try {
        $pong = Redis::connection()->ping();
        if ($pong !== true && strtoupper(ltrim((string) $pong, '+')) !== 'PONG') {
            throw new RuntimeException('Redis ping failed');
        }
        $this->info('Redis: OK');
    } catch (Throwable $error) {
        $this->error('Redis: FAILED (check server-side connection configuration)');
        $ok = false;
    }

    return $ok ? 0 : 1;
})->purpose('Check database and Redis connectivity without modifying data or exposing credentials');
