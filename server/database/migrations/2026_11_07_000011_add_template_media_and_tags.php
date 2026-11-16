<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('admin_user_id')->constrained()->restrictOnDelete();
            $table->string('disk', 40);
            $table->string('path');
            $table->string('mime', 80);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
        Schema::table('template_versions', function (Blueprint $table) {
            $table->foreignUuid('cover_media_id')->nullable()->constrained('template_media')->restrictOnDelete();
            $table->json('tags')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('template_versions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cover_media_id');
            $table->dropColumn(['tags', 'sort_order']);
        });
        Schema::dropIfExists('template_media');
    }
};
