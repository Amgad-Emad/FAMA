<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One password-reset token table per login entity. The talents and brands
 * brokers shared `password_reset_tokens` (keyed by email) with admin users, so
 * the same email registered as two account types would overwrite each other's
 * tokens. Same shape as the framework table.
 */
return new class extends Migration
{
    /**
     * Create the talent + brand token tables.
     */
    public function up(): void
    {
        foreach (['talent_password_reset_tokens', 'brand_password_reset_tokens'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    /**
     * Drop them.
     */
    public function down(): void
    {
        Schema::dropIfExists('talent_password_reset_tokens');
        Schema::dropIfExists('brand_password_reset_tokens');
    }
};
