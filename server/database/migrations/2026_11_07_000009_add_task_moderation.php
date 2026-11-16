<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generation_tasks', function (Blueprint $table) {
            $table->string('moderation_status', 30)->default('pending');
            $table->string('input_hash', 64)->nullable();
            $table->index(['user_id', 'input_hash', 'moderation_status'], 'task_input_moderation');
        });
        Schema::create('moderation_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('task_id')->unique()->constrained('generation_tasks')->restrictOnDelete();
            $table->foreignId('admin_user_id')->constrained()->restrictOnDelete();
            $table->uuid('idempotency_key');
            $table->string('decision', 30);
            $table->text('reason');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_reviews');
        Schema::table('generation_tasks', function (Blueprint $table) {
            $table->dropIndex('task_input_moderation');
            $table->dropColumn(['moderation_status', 'input_hash']);
        });
    }
};
