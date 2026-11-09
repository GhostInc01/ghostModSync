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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique()->after('name');
            $table->string('steam_id', 32)->nullable()->index()->after('email');
            $table->string('avatar_path', 255)->nullable()->after('steam_id');
            $table->boolean('is_platform_admin')->default(false)->after('remember_token');
            $table->timestamp('last_login_at')->nullable()->after('is_platform_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'steam_id',
                'avatar_path',
                'is_platform_admin',
                'last_login_at',
            ]);
        });
    }
};
