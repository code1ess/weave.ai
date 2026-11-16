<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role', 40);
            $table->boolean('active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->constrained()->restrictOnDelete();
            $table->string('action', 100);
            $table->string('subject', 100)->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['admin_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
        Schema::dropIfExists('admin_users');
    }
};
