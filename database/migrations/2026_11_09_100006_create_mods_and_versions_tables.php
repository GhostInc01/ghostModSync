<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('game_id')->constrained('games')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 150);
            $table->string('slug', 160);
            $table->string('category', 50)->default('general');
            $table->string('author_name', 120)->nullable();
            $table->text('description')->nullable();
            $table->string('source_type', 30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['group_id', 'game_id', 'slug']);
            $table->index(['group_id', 'game_id', 'is_active']);
        });

        Schema::create('mod_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mod_id')->constrained('mods')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('version_number', 50);
            $table->string('min_game_version', 32)->nullable();
            $table->string('max_game_version', 32)->nullable();
            $table->string('archive_filename', 255);
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->char('sha256_hash', 64)->nullable()->index();
            $table->text('changelog')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['mod_id', 'version_number']);
        });

        Schema::create('mod_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mod_version_id')->unique()->constrained('mod_versions')->cascadeOnDelete();
            $table->string('source_type', 30);
            $table->string('s3_disk', 30)->nullable()->default('s3');
            $table->string('s3_object_key', 500)->nullable();
            $table->string('s3_etag', 128)->nullable();
            $table->string('steam_published_file_id', 32)->nullable()->index();
            $table->string('steam_workshop_url', 500)->nullable();
            $table->timestamp('steam_last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mod_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mod_version_id')->constrained('mod_versions')->cascadeOnDelete();
            $table->foreignId('related_mod_id')->constrained('mods')->cascadeOnDelete();
            $table->foreignId('related_mod_version_id')->nullable()->constrained('mod_versions')->nullOnDelete();
            $table->string('dependency_type', 20)->default('requires');
            $table->timestamps();

            $table->unique(['mod_version_id', 'related_mod_id', 'dependency_type'], 'mod_dep_version_related_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mod_dependencies');
        Schema::dropIfExists('mod_sources');
        Schema::dropIfExists('mod_versions');
        Schema::dropIfExists('mods');
    }
};
