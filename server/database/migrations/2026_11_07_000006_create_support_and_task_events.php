<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('task_id')->constrained('generation_tasks')->restrictOnDelete();
            $table->string('type', 40);
            $table->timestamp('created_at')->useCurrent();
        });
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('task_id')->nullable()->constrained('generation_tasks')->restrictOnDelete();
            $table->uuid('idempotency_key');
            $table->string('subject', 160);
            $table->text('body');
            $table->string('status', 30)->default('open');
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key']);
            $table->index(['user_id', 'updated_at']);
            $table->index(['status', 'updated_at']);
        });
        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('ticket_id')->constrained('support_tickets')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('admin_user_id')->nullable()->constrained()->restrictOnDelete();
            $table->uuid('idempotency_key');
            $table->text('body');
            $table->string('requested_status', 30);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['ticket_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('task_events');
    }
};
