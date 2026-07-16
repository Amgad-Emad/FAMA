<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brands — MINIMAL stub (Phase 1E, contract engine).
 *
 * The contract engine references `contracts.brand_id → brands`, so the table must exist
 * now. This creates only the auth surface + public identity + the `is_complete`
 * contract-flow gate — enough to reference and to seed test brands. The full brand
 * core (industry, stage, location, reach, aesthetics & satellites per
 * schema-master §4) is Phase 1B and EXTENDS this table (adds columns); it does
 * not recreate it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();

            // Auth surface (the `brand` guard / `brands` provider).
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->string('phone')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->boolean('is_active')->default(true);

            // Public identity — fama.com/brands/{slug}.
            $table->string('slug')->unique();
            $table->string('name');

            // Gates / flags (Phase 1B fills the rest of the profile).
            $table->boolean('is_complete')->default(false);   // contract-flow gate
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('view_count')->default(0);
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('is_complete');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
