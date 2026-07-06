<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Discovery/search indexes (ADR-6 / talent-spec discovery page). The
 * query-critical dimensions are already relational (talent types via the
 * talent_talent_type pivot; equipment and software_stack as tables); this adds
 * the missing indexes so filtering by type, availability, location, equipment
 * and software is cheap. Recorded in docs/schema.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talents', function (Blueprint $table) {
            $table->index('availability_status');
            $table->index('is_published');
            $table->index('base_city');
            $table->index('base_country');
        });

        // Reverse pivot lookup: "talents who work as type X".
        Schema::table('talent_talent_type', function (Blueprint $table) {
            $table->index('talent_type_id');
        });

        // Cross-talent gear/tool filters: "who owns a camera / knows Figma".
        Schema::table('equipment', function (Blueprint $table) {
            $table->index('category');
        });

        Schema::table('software_stack', function (Blueprint $table) {
            $table->index('software_name');
        });
    }

    public function down(): void
    {
        Schema::table('talents', function (Blueprint $table) {
            $table->dropIndex(['availability_status']);
            $table->dropIndex(['is_published']);
            $table->dropIndex(['base_city']);
            $table->dropIndex(['base_country']);
        });
        Schema::table('talent_talent_type', fn (Blueprint $table) => $table->dropIndex(['talent_type_id']));
        Schema::table('equipment', fn (Blueprint $table) => $table->dropIndex(['category']));
        Schema::table('software_stack', fn (Blueprint $table) => $table->dropIndex(['software_name']));
    }
};
