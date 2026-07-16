<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Availability & travel removal (ADR-L): drops `availability_status` (+ its
 * discovery index), `rate_tier`, `willing_to_travel` and `travel_regions` from
 * `talents`. Enquiries are no longer gated by availability, and `rate_tier` is
 * superseded by the single Pricing rate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talents', function (Blueprint $table): void {
            $table->dropIndex(['availability_status']);
            $table->dropColumn(['availability_status', 'rate_tier', 'willing_to_travel', 'travel_regions']);
        });
    }

    public function down(): void
    {
        Schema::table('talents', function (Blueprint $table): void {
            $table->enum('availability_status', ['available', 'booked', 'unavailable'])->default('available');
            $table->enum('rate_tier', ['emerging', 'established', 'premium', 'elite'])->nullable();
            $table->boolean('willing_to_travel')->default(false);
            $table->json('travel_regions')->nullable();
            $table->index('availability_status');
        });
    }
};
