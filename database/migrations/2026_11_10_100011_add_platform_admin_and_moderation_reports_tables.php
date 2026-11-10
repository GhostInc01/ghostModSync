<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('platform_role', 30)->nullable()->index()->after('is_platform_admin');
            $table->timestamp('suspended_at')->nullable()->index()->after('last_login_at');
            $table->string('suspended_reason', 500)->nullable()->after('suspended_at');
        });

        Schema::create('moderation_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reportable_type', 120);
            $table->unsignedBigInteger('reportable_id');
            $table->string('reason', 120);
            $table->text('details')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_reports');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'platform_role',
                'suspended_at',
                'suspended_reason',
            ]);
        });
    }
};
