<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'generation_tasks', 'wallet_ledger'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->index('created_at', $name.'_report_created_at_index'));
        }
    }

    public function down(): void
    {
        foreach (['users', 'generation_tasks', 'wallet_ledger'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropIndex($name.'_report_created_at_index'));
        }
    }
};
