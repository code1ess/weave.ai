<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_contents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 100)->unique();
            $table->string('kind', 30);
            $table->unsignedInteger('current_version')->default(1);
            $table->unsignedInteger('published_version')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['kind', 'published_at']);
        });
        Schema::create('site_content_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('content_id')->constrained('site_contents')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->string('title', 160);
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['content_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_content_versions');
        Schema::dropIfExists('site_contents');
    }
};
