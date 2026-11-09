<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->boolean('require_mod_approval')->default(true)->after('require_join_approval');
        });

        Schema::create('group_join_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('review_notes', 500)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['group_id', 'user_id', 'status']);
        });

        Schema::create('group_mod_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('mod_version_id')->constrained('mod_versions')->cascadeOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->boolean('checksum_verified')->default(false);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'mod_version_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_mod_approvals');
        Schema::dropIfExists('group_join_requests');

        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('require_mod_approval');
        });
    }
};
