<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Follow-suggestion dismissals (revision 5Z) — a dismissed suggestion never
 * returns — plus brands.followers_count: feed.allow_following_brands flipped
 * TRUE with the brand rail, so the deliberately-omitted counter (schema-master
 * §7 note) lands now, maintained in-transaction and reconciled nightly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suggestion_dismissals', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type');
            $table->unsignedBigInteger('actor_id');
            $table->string('suggestible_type');
            $table->unsignedBigInteger('suggestible_id');
            $table->timestamp('dismissed_at');

            $table->unique(['actor_type', 'actor_id', 'suggestible_type', 'suggestible_id'], 'suggestion_dismissals_unique');
            $table->index(['actor_type', 'actor_id']);
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->unsignedInteger('followers_count')->default(0)->after('view_count');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('followers_count');
        });

        Schema::dropIfExists('suggestion_dismissals');
    }
};
