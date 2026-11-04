<?php

use App\Events\TalentProfileViewed;
use App\Models\Talent;
use App\Models\TalentProfileView;
use App\Services\ProfileViewStatsService;
use Carbon\CarbonImmutable;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);
});

/** Insert a daily bucket row for the talent. */
function seedViews(Talent $talent, string $date, int $views): void
{
    TalentProfileView::create(['talent_id' => $talent->id, 'viewed_on' => $date, 'views' => $views]);
}

// --- Daily bucket recording --------------------------------------------------

it('rolls profile views into a daily bucket alongside the lifetime counter', function () {
    $talent = Talent::factory()->create(['view_count' => 0]);

    TalentProfileViewed::dispatch($talent);
    TalentProfileViewed::dispatch($talent);

    $this->assertDatabaseHas('talent_profile_views', [
        'talent_id' => $talent->id,
        'viewed_on' => now()->toDateString(),
        'views' => 2,
    ]);
    expect(TalentProfileView::where('talent_id', $talent->id)->count())->toBe(1)
        ->and($talent->fresh()->view_count)->toBe(2);
});

// --- Weekly comparison -------------------------------------------------------

it('compares the rolling week with the previous one', function () {
    $this->travelTo('2026-08-10 12:00:00'); // a Monday
    $talent = Talent::factory()->create();

    seedViews($talent, '2026-08-10', 3); // today → current week
    seedViews($talent, '2026-08-04', 2); // 6 days ago → current week
    seedViews($talent, '2026-08-03', 4); // 7 days ago → previous week
    seedViews($talent, '2026-07-28', 1); // 13 days ago → previous week
    seedViews($talent, '2026-07-27', 9); // 14 days ago → outside both windows

    $weekly = app(ProfileViewStatsService::class)->weeklyComparison($talent);

    expect($weekly)->toBe(['current_week' => 5, 'previous_week' => 5, 'delta' => 0, 'delta_pct' => 0.0]);
});

it('reports no percentage when the previous week had no views', function () {
    $this->travelTo('2026-08-10 12:00:00');
    $talent = Talent::factory()->create();
    seedViews($talent, '2026-08-09', 7);

    $weekly = app(ProfileViewStatsService::class)->weeklyComparison($talent);

    expect($weekly['current_week'])->toBe(7)
        ->and($weekly['previous_week'])->toBe(0)
        ->and($weekly['delta'])->toBe(7)
        ->and($weekly['delta_pct'])->toBeNull();
});

// --- Chart series endpoint ---------------------------------------------------

it('returns a zero-filled daily series for the 7d range', function () {
    $this->travelTo('2026-08-10 12:00:00');
    $talent = Talent::factory()->create();
    seedViews($talent, '2026-08-10', 3);
    seedViews($talent, '2026-08-06', 2);
    seedViews($talent, '2026-08-01', 8); // outside the 7-day window

    $response = $this->actingAs($talent, 'talent')
        ->getJson(route('talent.dashboard.views', ['range' => '7d']))
        ->assertOk()
        ->assertJsonPath('data.range', '7d')
        ->assertJsonPath('data.bucket', 'day')
        ->assertJsonPath('data.total', 5)
        ->assertJsonCount(7, 'data.series');

    $series = collect($response->json('data.series'));
    expect($series->firstWhere('date', '2026-08-06')['views'])->toBe(2)
        ->and($series->firstWhere('date', '2026-08-10')['views'])->toBe(3)
        ->and($series->firstWhere('date', '2026-08-08')['views'])->toBe(0);
});

it('buckets the 90d range by week', function () {
    $this->travelTo('2026-08-10 12:00:00');
    $talent = Talent::factory()->create();
    seedViews($talent, '2026-08-10', 1); // Monday — its own week bucket
    seedViews($talent, '2026-08-04', 2); // previous week (Mon 2026-08-03)
    seedViews($talent, '2026-08-06', 5); // same previous week

    $response = $this->actingAs($talent, 'talent')
        ->getJson(route('talent.dashboard.views', ['range' => '90d']))
        ->assertOk()
        ->assertJsonPath('data.bucket', 'week');

    $series = collect($response->json('data.series'));
    expect($series->firstWhere('date', '2026-08-03')['views'])->toBe(7)
        ->and($series->firstWhere('date', '2026-08-10')['views'])->toBe(1);
});

it('reaches back to the profile creation date for the all range', function () {
    $this->travelTo('2026-08-10 12:00:00');
    $talent = Talent::factory()->create(['created_at' => CarbonImmutable::parse('2026-08-01')]);
    seedViews($talent, '2026-08-01', 4);

    $this->actingAs($talent, 'talent')
        ->getJson(route('talent.dashboard.views', ['range' => 'all']))
        ->assertOk()
        ->assertJsonPath('data.bucket', 'day') // 9-day span → daily buckets
        ->assertJsonPath('data.total', 4)
        ->assertJsonPath('data.series.0.date', '2026-08-01')
        ->assertJsonCount(10, 'data.series');
});

it('rejects an unknown range', function () {
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')
        ->getJson(route('talent.dashboard.views', ['range' => 'forever']))
        ->assertStatus(422);
});

it('requires the talent guard', function () {
    $this->getJson(route('talent.dashboard.views'))->assertStatus(401);
});

// --- Views card (folded into Profile structure, fama-ui 2026-10-12) ---------

it('shows the views card with the lifetime total and the weekly delta on Profile structure', function () {
    $this->travelTo('2026-08-10 12:00:00');
    $talent = Talent::factory()->create(['view_count' => 1234]);
    seedViews($talent, '2026-08-09', 3);

    $this->actingAs($talent, 'talent')->get(route('talent.profile.edit'))
        ->assertOk()
        ->assertSee('Profile views · all time')
        ->assertSee('profileViews', false)
        ->assertSee('1,234')
        ->assertSee('▲ 3')
        ->assertSee(__('on last week'))
        ->assertDontSee('Pending reviews')
        ->assertDontSee('Blocks · skills');
});
