<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generation_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('idempotency_key');
            $table->string('kind', 40);
            $table->text('prompt');
            $table->string('status', 30)->default('pending');
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generation_tasks');
    }
};
