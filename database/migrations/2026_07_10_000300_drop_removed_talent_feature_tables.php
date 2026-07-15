<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the tables of the three removed talent features — the rate card
 * (`services`, ADR-K), affiliations (`agency_affiliations`) and press
 * (`press_features`) — the latter two per ADR-M. Runs after
 * `contracts.service_id` / `contract_enquiries.service_id` have been dropped
 * (2026_07_10_000100).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('services');
        Schema::dropIfExists('agency_affiliations');
        Schema::dropIfExists('press_features');
    }

    public function down(): void
    {
        // One-way removal — the feature tables are not restored (see the create
        // migration 2026_07_05_000400_create_talent_content_tables for their old
        // shape if ever needed).
    }
};
