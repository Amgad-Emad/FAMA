<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refines the admin `users` table (schema-master §6): bilingual locale, an
 * activity flag + last-login stamp, an optional avatar/phone, and soft deletes.
 * Admin *roles* are modelled with spatie/laravel-permission (see ADR-H), so no
 * `role` column is added here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar_url')->nullable()->after('phone');
            $table->enum('locale', ['en', 'ar'])->nullable()->after('avatar_url');
            $table->timestamp('last_login_at')->nullable()->after('locale');
            $table->boolean('is_active')->default(true)->after('last_login_at');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'avatar_url', 'locale', 'last_login_at', 'is_active', 'deleted_at']);
        });
    }
};
