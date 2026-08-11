<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daily-bucketed profile-view counts. `talents.view_count` stays the lifetime
 * total; this table adds the time dimension the dashboard KPI + chart need
 * (week-over-week comparison, range-filtered series back to profile creation).
 * One row per talent per day, incremented by RecordDailyProfileView.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent_profile_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->date('viewed_on');
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();

            $table->unique(['talent_id', 'viewed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_profile_views');
    }
};
