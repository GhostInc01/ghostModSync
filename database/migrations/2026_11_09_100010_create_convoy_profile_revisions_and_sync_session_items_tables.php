<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('convoy_profile_revisions')) {
            Schema::create('convoy_profile_revisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('convoy_profile_id')->constrained('convoy_profiles')->cascadeOnDelete();
                $table->unsignedInteger('revision_number');
                $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('target_game_version', 32);
                $table->char('manifest_hash', 64)->index();
                $table->text('changelog')->nullable();
                $table->json('snapshot_json');
                $table->timestamp('published_at')->useCurrent();
                $table->timestamps();

                $table->unique(['convoy_profile_id', 'revision_number'], 'cp_rev_profile_revnum_unique');
            });
        }

        if (! Schema::hasTable('convoy_profile_revision_mods')) {
            Schema::create('convoy_profile_revision_mods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('convoy_profile_revision_id')->constrained('convoy_profile_revisions')->cascadeOnDelete();
                $table->foreignId('mod_version_id')->constrained('mod_versions')->restrictOnDelete();
                $table->unsignedInteger('load_order');
                $table->boolean('is_required')->default(true);
                $table->string('notes', 255)->nullable();
                $table->timestamps();

                $table->unique(['convoy_profile_revision_id', 'load_order'], 'cp_rev_mods_rev_order_unique');
                $table->unique(['convoy_profile_revision_id', 'mod_version_id'], 'cp_rev_mods_rev_modver_unique');
            });
        }

        if (! Schema::hasColumn('convoy_profiles', 'current_revision_id')) {
            Schema::table('convoy_profiles', function (Blueprint $table) {
                $table->foreignId('current_revision_id')
                    ->nullable()
                    ->after('game_id')
                    ->constrained('convoy_profile_revisions')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('sync_approvals', 'convoy_profile_revision_id')) {
            Schema::table('sync_approvals', function (Blueprint $table) {
                $table->foreignId('convoy_profile_revision_id')
                    ->nullable()
                    ->after('convoy_profile_id')
                    ->constrained('convoy_profile_revisions')
                    ->restrictOnDelete();
            });
        }

        if (! Schema::hasColumn('sync_sessions', 'convoy_profile_revision_id')) {
            Schema::table('sync_sessions', function (Blueprint $table) {
                // Add the replacement composite index starting with convoy_profile_id FIRST
                // so MySQL InnoDB has an index backing the convoy_profile_id foreign key before dropping the unique index.
                $table->index(['convoy_profile_id', 'device_id', 'id'], 'sync_sessions_profile_device_id_idx');
            });

            Schema::table('sync_sessions', function (Blueprint $table) {
                $table->dropUnique(['convoy_profile_id', 'device_id']);

                $table->foreignId('convoy_profile_revision_id')
                    ->nullable()
                    ->after('convoy_profile_id')
                    ->constrained('convoy_profile_revisions')
                    ->restrictOnDelete();

                $table->unsignedBigInteger('bytes_downloaded')->default(0)->after('extra_local_mods_count');
                $table->unsignedInteger('duration_ms')->nullable()->after('bytes_downloaded');
                $table->timestamp('started_at')->nullable()->after('error_message');
            });
        }

        if (! Schema::hasTable('sync_session_items')) {
            Schema::create('sync_session_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sync_session_id')->constrained('sync_sessions')->cascadeOnDelete();
                $table->foreignId('mod_version_id')->constrained('mod_versions')->restrictOnDelete();
                $table->string('action_taken', 30);
                $table->string('status', 25);
                $table->char('local_sha256_hash', 64)->nullable();
                $table->unsignedBigInteger('bytes_downloaded')->default(0);
                $table->string('error_detail', 500)->nullable();
                $table->timestamps();

                $table->unique(['sync_session_id', 'mod_version_id'], 'sync_session_items_session_modver_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_session_items');

        Schema::table('sync_sessions', function (Blueprint $table) {
            $table->dropIndex('sync_sessions_profile_device_id_idx');
            $table->dropConstrainedForeignId('convoy_profile_revision_id');
            $table->dropColumn(['bytes_downloaded', 'duration_ms', 'started_at']);
            $table->unique(['convoy_profile_id', 'device_id']);
        });

        Schema::table('sync_approvals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('convoy_profile_revision_id');
        });

        Schema::table('convoy_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_revision_id');
        });

        Schema::dropIfExists('convoy_profile_revision_mods');
        Schema::dropIfExists('convoy_profile_revisions');
    }
};
