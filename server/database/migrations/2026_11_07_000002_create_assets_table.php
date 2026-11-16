<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('mime', 80);
            $table->unsignedBigInteger('size');
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
        Schema::table('generation_tasks', function (Blueprint $table) {
            $table->foreignUuid('asset_id')->nullable()->constrained('assets')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('generation_tasks', fn (Blueprint $table) => $table->dropConstrainedForeignId('asset_id'));
        Schema::dropIfExists('assets');
    }
};
