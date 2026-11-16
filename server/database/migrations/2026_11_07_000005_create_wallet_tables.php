<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('available')->default(0);
            $table->unsignedBigInteger('reserved')->default(0);
            $table->timestamps();
        });
        Schema::create('wallet_ledger', function (Blueprint $table) {
            $table->bigIncrements('sequence');
            $table->uuid('id')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->uuid('idempotency_key');
            $table->bigInteger('amount');
            $table->unsignedBigInteger('available_after');
            $table->string('type', 40);
            $table->text('reason');
            $table->foreignId('admin_user_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'idempotency_key']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_ledger');
        Schema::dropIfExists('wallets');
    }
};
