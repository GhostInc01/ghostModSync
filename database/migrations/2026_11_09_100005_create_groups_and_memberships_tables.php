<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->string('tag', 16)->nullable();
            $table->text('description')->nullable();
            $table->string('visibility', 20)->default('private')->index();
            $table->string('sync_policy', 30)->default('automatic');
            $table->boolean('require_join_approval')->default(true);
            $table->string('logo_path', 255)->nullable();
            $table->string('banner_path', 255)->nullable();
            $table->unsignedBigInteger('storage_quota_bytes')->default(53687091200); // 50 GB
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('group_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 30)->default('member');
            $table->string('status', 20)->default('active')->index();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'user_id']);
        });

        Schema::create('group_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('email', 255)->nullable()->index();
            $table->char('token', 64)->unique();
            $table->string('role', 30)->default('member');
            $table->unsignedInteger('max_uses')->nullable()->default(1);
            $table->unsignedInteger('uses_count')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('group_id')->references('id')->on('groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
        });

        Schema::dropIfExists('group_invitations');
        Schema::dropIfExists('group_memberships');
        Schema::dropIfExists('groups');
    }
};
