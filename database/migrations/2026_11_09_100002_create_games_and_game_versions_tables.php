<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('name', 100);
            $table->unsignedInteger('steam_app_id');
            $table->string('default_mod_dir_name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('game_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->string('version_branch', 32);
            $table->boolean('is_current')->default(false);
            $table->date('released_at')->nullable();
            $table->timestamps();

            $table->unique(['game_id', 'version_branch']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_versions');
        Schema::dropIfExists('games');
    }
};
