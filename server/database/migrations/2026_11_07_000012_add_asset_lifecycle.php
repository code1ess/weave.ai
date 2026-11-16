<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('status', 30)->default('active');
            $table->timestamp('deleted_at')->nullable();
            $table->index(['user_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status', 'created_at']);
            $table->dropColumn(['status', 'deleted_at']);
        });
    }
};
