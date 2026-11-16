<?php

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' || realpath(config('database.connections.sqlite.database')) !== realpath(storage_path('framework/testing/browser.sqlite'))) {
    throw new RuntimeException('Browser fixtures require the dedicated browser SQLite database.');
}
User::create(['name' => 'Browser User', 'email' => 'browser@example.test', 'password' => 'Browser-test-password-123']);
AdminUser::create(['name' => 'Browser Admin', 'email' => 'admin@example.test', 'password' => 'Browser-test-password-123', 'role' => 'superadmin', 'active' => true]);
