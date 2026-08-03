<?php

use App\Actions\Feed\ToggleFollow;
use App\Models\Talent;
use Illuminate\Support\Facades\DB;

// ----- 5U: ranking + exclusions + fixed query count --------------------------

it('ranks followed people first, then prefix matches, and excludes the current actor', function () {
    $viewer = Talent::factory()->create(['slug' => 'rana-viewer', 'display_name' => 'Rana Viewer']);
    $followedSubstring = Talent::factory()->create(['slug' => 'sub-rana', 'display_name' => 'Sub Rana']);
    $prefix = Talent::factory()->create(['slug' => 'rana-prefix', 'display_name' => 'Rana Prefix']);
    Talent::factory()->create(['slug' => 'zz-rana', 'display_name' => 'Zz Rana']); // substring, unfollowed

    app(ToggleFollow::class)($viewer, $followedSubstring, onlyFollow: true);

    $rows = collect($this->actingAs($viewer, 'talent')
        ->getJson('/mentions/search?q=rana')
        ->assertOk()
        ->json('data.talents'));

    // Followed beats prefix beats substring; the viewer never appears.
    expect($rows->pluck('username')->all())->toBe(['sub-rana', 'rana-prefix', 'zz-rana']);
});

it('answers with a fixed query count regardless of result size', function () {
    $viewer = Talent::factory()->create(['slug' => 'the-viewer']);

    $count = function (string $q) use ($viewer): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($viewer, 'talent')->getJson('/mentions/search?q='.$q)->assertOk();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queries;
    };

    Talent::factory()->count(2)->create(['display_name' => 'Query Small']);
    $small = $count('query');

    Talent::factory()->count(10)->create(['display_name' => 'Query Large']);
    $large = $count('query');

    expect($large)->toBe($small);
});
