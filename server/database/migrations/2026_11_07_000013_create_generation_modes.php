<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generation_modes', function (Blueprint $table) {
            $table->string('kind', 40)->primary();
            $table->boolean('active')->default(true);
            $table->json('aspect_ratios');
            $table->string('pause_message', 200)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });
        foreach (['text_to_image', 'image_to_image', 'image_to_video'] as $kind) {
            DB::table('generation_modes')->insert(['kind' => $kind, 'active' => true, 'aspect_ratios' => json_encode(['1:1', '16:9', '9:16', '4:3', '3:4']), 'version' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
        Schema::table('generation_tasks', function (Blueprint $table) {
            $table->json('parameters')->nullable();
            $table->unsignedInteger('mode_version')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('generation_tasks', fn (Blueprint $table) => $table->dropColumn(['parameters', 'mode_version']));
        Schema::dropIfExists('generation_modes');
    }
};
