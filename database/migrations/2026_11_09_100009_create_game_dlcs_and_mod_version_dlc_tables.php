<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_dlcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 120);
            $table->unsignedInteger('steam_app_id')->unique();
            $table->string('category', 30)->default('map');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['game_id', 'code']);
        });

        Schema::create('mod_version_dlc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mod_version_id')->constrained('mod_versions')->cascadeOnDelete();
            $table->foreignId('game_dlc_id')->constrained('game_dlcs')->cascadeOnDelete();
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['mod_version_id', 'game_dlc_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mod_version_dlc');
        Schema::dropIfExists('game_dlcs');
    }
};
