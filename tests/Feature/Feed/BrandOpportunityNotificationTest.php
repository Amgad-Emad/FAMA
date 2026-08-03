<?php

use App\Actions\Feed\ToggleFollow;
use App\Models\Brand;
use App\Models\BrandProject;
use App\Models\Talent;
use App\Notifications\Feed\BrandOpportunityPosted;
use App\Services\BrandProjectService;
use Illuminate\Support\Facades\Notification;

/**
 * The notify-bell (talent→brand subscription): when a brand opens a PUBLIC
 * opportunity, every subscribed talent is notified with a deep link to apply.
 */

it('notifies subscribed talents when a brand opens a public opportunity', function () {
    Notification::fake();

    $brand = Brand::factory()->create(['is_published' => true]);
    $subscriber = Talent::factory()->create();
    $bystander = Talent::factory()->create();

    // The subscriber taps the bell (reuses the follow edge).
    app(ToggleFollow::class)($subscriber, $brand, onlyFollow: true);

    $project = BrandProject::factory()->create([
        'brand_id' => $brand->id,
        'status' => 'draft',
        'is_public' => true,
    ]);

    app(BrandProjectService::class)->open($project);

    Notification::assertSentTo($subscriber, BrandOpportunityPosted::class);
    Notification::assertNotSentTo($bystander, BrandOpportunityPosted::class);
});

it('does not broadcast a private opportunity', function () {
    Notification::fake();

    $brand = Brand::factory()->create(['is_published' => true]);
    $subscriber = Talent::factory()->create();
    app(ToggleFollow::class)($subscriber, $brand, onlyFollow: true);

    $project = BrandProject::factory()->create([
        'brand_id' => $brand->id,
        'status' => 'draft',
        'is_public' => false,
    ]);

    app(BrandProjectService::class)->open($project);

    Notification::assertNotSentTo($subscriber, BrandOpportunityPosted::class);
});
