<?php

use App\Models\Follow;
use App\Models\Post;
use App\Models\ProjectCredit;
use App\Models\Recognition;
use App\Models\Talent;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\ScreensDemoSeeder;
use Database\Seeders\SpecialitySeeder;
use Database\Seeders\TalentDemoSeeder;
use Database\Seeders\TalentShowcaseSeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed the catalogs + demo + showcase talents, a few posts, then the screens top-up.
 */
function seedScreens($test): Talent
{
    $test->seed([TalentTypeSeeder::class, SpecialitySeeder::class, BlockTypeSeeder::class, TalentDemoSeeder::class, TalentShowcaseSeeder::class]);
    Post::factory()->count(8)->create();
    $test->seed(ScreensDemoSeeder::class);

    return Talent::where('email', 'talent-demo@fama.test')->firstOrFail();
}

it('fills every fama-ui screen for the demo talent', function () {
    $demo = seedScreens($this);

    expect(DB::table('talent_profile_views')->where('talent_id', $demo->id)->count())->toBe(120)
        ->and(DB::table('talent_social_handles')->where('talent_id', $demo->id)->count())->toBeGreaterThanOrEqual(5)
        ->and($demo->projects()->count())->toBeGreaterThanOrEqual(3)
        ->and(ProjectCredit::where('talent_id', $demo->id)->where('status', 'pending')->count())->toBe(2)
        ->and(Recognition::where('talent_id', $demo->id)->where('status', 'pending')->exists())->toBeTrue()
        ->and(Follow::where('followable_type', 'talent')->where('followable_id', $demo->id)->count())->toBeGreaterThan(0)
        ->and($demo->notifications()->count())->toBe(9)
        ->and($demo->unreadNotifications()->count())->toBe(4);

    // A co-owner exists on one of the demo talent's projects (accepted through the service).
    $projectIds = $demo->projects()->pluck('id');
    expect(ProjectCredit::whereIn('project_id', $projectIds)->where('status', 'accepted')->exists())->toBeTrue()
        ->and(ProjectCredit::whereIn('project_id', $projectIds)->where('status', 'listed')->exists())->toBeTrue();

    // The Private, unverified talent for the gate states.
    $new = Talent::where('email', 'talent-new@fama.test')->firstOrFail();
    expect($new->is_published)->toBeFalse()->and($new->is_verified)->toBeFalse()->and($new->talentTypes()->count())->toBe(1);
});

it('is idempotent', function () {
    $demo = seedScreens($this);
    $before = [$demo->projects()->count(), ProjectCredit::count(), Recognition::count(), Follow::count(), $demo->notifications()->count()];

    $this->seed(ScreensDemoSeeder::class);

    expect([$demo->projects()->count(), ProjectCredit::count(), Recognition::count(), Follow::count(), $demo->notifications()->count()])
        ->toBe($before);
});

it('slides an aged feed forward with demo:freshen', function () {
    $old = Post::factory()->create(['published_at' => now()->subDays(60)]);
    $older = Post::factory()->create(['published_at' => now()->subDays(62)]);

    $this->artisan('demo:freshen')->assertSuccessful();

    $old->refresh();
    $older->refresh();
    expect($old->published_at->diffInMinutes(now()))->toBeLessThan(65)
        // Relative order is kept: still two days apart.
        ->and((int) round($older->published_at->diffInHours($old->published_at)))->toBe(48);

    $this->artisan('demo:freshen')->expectsOutput('The feed is already fresh.')->assertSuccessful();
});
