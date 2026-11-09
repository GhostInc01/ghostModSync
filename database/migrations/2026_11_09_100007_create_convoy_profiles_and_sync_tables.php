<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('convoy_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('game_id')->constrained('games')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 150);
            $table->string('slug', 160);
            $table->text('description')->nullable();
            $table->string('target_game_version', 32);
            $table->string('sync_policy_override', 30)->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->char('manifest_hash', 64);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('scheduled_convoy_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['group_id', 'slug']);
            $table->index(['group_id', 'game_id', 'is_active']);
        });

        Schema::create('convoy_profile_mods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convoy_profile_id')->constrained('convoy_profiles')->cascadeOnDelete();
            $table->foreignId('mod_version_id')->constrained('mod_versions')->restrictOnDelete();
            $table->unsignedInteger('load_order');
            $table->boolean('is_required')->default(true);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['convoy_profile_id', 'load_order']);
            $table->unique(['convoy_profile_id', 'mod_version_id']);
        });

        Schema::create('sync_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convoy_profile_id')->constrained('convoy_profiles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->unsignedInteger('approved_revision');
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['convoy_profile_id', 'user_id', 'status']);
        });

        Schema::create('sync_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convoy_profile_id')->constrained('convoy_profiles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->unsignedInteger('profile_revision');
            $table->char('reported_manifest_hash', 64)->nullable();
            $table->string('status', 25);
            $table->unsignedInteger('missing_mods_count')->default(0);
            $table->unsignedInteger('mismatched_mods_count')->default(0);
            $table->unsignedInteger('extra_local_mods_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['convoy_profile_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_sessions');
        Schema::dropIfExists('sync_approvals');
        Schema::dropIfExists('convoy_profile_mods');
        Schema::dropIfExists('convoy_profiles');
    }
};
