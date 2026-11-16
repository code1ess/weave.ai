<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('status', 30)->default('draft');
            $table->unsignedInteger('current_version')->default(1);
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
        Schema::create('template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('template_id')->constrained('templates')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->string('title', 120);
            $table->text('description');
            $table->string('kind', 40);
            $table->text('prompt');
            $table->string('category', 80);
            $table->text('source_license');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['template_id', 'version']);
        });
        Schema::table('generation_tasks', function (Blueprint $table) {
            $table->foreignUuid('template_id')->nullable()->constrained('templates')->restrictOnDelete();
            $table->unsignedInteger('template_version')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('generation_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('template_id');
            $table->dropColumn('template_version');
        });
        Schema::dropIfExists('template_versions');
        Schema::dropIfExists('templates');
    }
};
