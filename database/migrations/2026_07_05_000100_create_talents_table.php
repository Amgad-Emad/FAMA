<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `talents` — the living creative passport (schema-master §1).
 *
 * One-per-profile identity + singular settings. Uploaded assets (hero, avatar)
 * are NOT columns here: they live in the media library and are exposed via
 * accessors (App\Models\Talent). Translatable copy (headline, bio) is stored as
 * JSON per locale. Profile fields are nullable so a talent can sign up first and
 * fill the profile progressively (created → draft → published).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talents', function (Blueprint $table) {
            $table->id();

            // Auth surface (talent guard).
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone')->nullable();
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->boolean('is_active')->default(true);

            // Public identity.
            $table->string('slug')->unique(); // fama.com/{slug}
            $table->string('display_name')->nullable();
            $table->json('headline')->nullable();   // translatable
            $table->json('bio')->nullable();         // translatable

            // Singular settings (one value per talent).
            $table->enum('availability_status', ['available', 'booked', 'unavailable'])->default('available');
            $table->string('base_city')->nullable();
            $table->string('base_country')->nullable();
            $table->enum('rate_tier', ['emerging', 'established', 'premium', 'elite'])->nullable();
            $table->boolean('willing_to_travel')->default(false);
            $table->json('travel_regions')->nullable();
            $table->enum('booking_type', ['email', 'calendar', 'form', 'external'])->default('email');
            $table->string('booking_value')->nullable();

            // Publication + housekeeping.
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talents');
    }
};
