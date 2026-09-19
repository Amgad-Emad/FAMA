<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The portfolio spec's "Now" and "About" fields (ADR-Z).
 *
 * NOW — everything time-sensitive, in one strip at the top, because it is what a
 * brand checks before anything else:
 *  - `will_travel`  ON or OFF. Whether they will work outside their city AT ALL.
 *    Deliberately a boolean and NOT a list of cities: which city and on whose
 *    budget is a conversation, and starting that conversation is the point.
 *  - `is_available` ON or OFF. Taking work, or not.
 *  - `availability_changed_at` — availability goes stale faster than anything
 *    else on the page, so the public profile shows WHEN it was last confirmed.
 *    Without the date the toggle is worse than useless: it looks current forever.
 *
 * This supersedes the part of ADR-L that removed availability & travel. It is
 * NOT a revert: the old shape was an `availability_status` ENUM (available /
 * booked / unavailable) plus `willing_to_travel` + a `travel_regions` JSON list.
 * The spec asks for two plain toggles, so these are new columns with new names
 * and simpler semantics, not the old ones restored. ADR-L's other half —
 * enquiries are never gated on availability — still stands: `is_available` is
 * INFORMATION, never a gate.
 *
 * ABOUT — `worldview` is taste and point of view (what they are drawn to, what
 * they turn down, who they want to work with), kept apart from `bio`, which is
 * background and track record. One field for both collapses into a second bio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talents', function (Blueprint $table): void {
            $table->boolean('will_travel')->default(false)->after('base_country');
            $table->boolean('is_available')->default(true)->after('will_travel');
            $table->timestamp('availability_changed_at')->nullable()->after('is_available');
            $table->json('worldview')->nullable()->after('bio'); // translatable

            // Discovery filters on "available now", so it needs to be indexed.
            $table->index('is_available');
        });
    }

    public function down(): void
    {
        Schema::table('talents', function (Blueprint $table): void {
            $table->dropIndex(['is_available']);
            $table->dropColumn(['will_travel', 'is_available', 'availability_changed_at', 'worldview']);
        });
    }
};
