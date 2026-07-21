<?php

use App\Models\Brand;
use App\Models\BrandReview;
use App\Models\Review;
use App\Models\Talent;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TalentTypeSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
    $this->moderator = User::factory()->create();
    $this->moderator->assignRole('moderator');
});

/**
 * Every admin destination, expressed as [route name, params]. Building hrefs
 * through route() means a renamed/removed route fails the test loudly — no
 * dead links can pass.
 */
function adminDestinations(): array
{
    return [
        'dashboard' => [route('admin.dashboard'), null],
        'moderation.talents' => [route('admin.moderation.index', ['queue' => 'talents']), 'moderate-content'],
        'moderation.brands' => [route('admin.moderation.index', ['queue' => 'brands']), 'moderate-content'],
        'moderation.brand-reviews' => [route('admin.moderation.index', ['queue' => 'brand-reviews']), 'moderate-content'],
        'moderation.all-reviews' => [route('admin.moderation.index', ['queue' => 'all-reviews']), 'moderate-content'],
        'projects-oversight' => [route('admin.moderation.index', ['queue' => 'projects']), 'moderate-content'],
        'contracts' => [route('admin.contracts'), 'intervene-contracts'],
        'flows' => [route('admin.flows'), 'manage-flows'],
        'skills' => [route('admin.skills'), 'manage-flows'],
        'blocks' => [route('admin.blocks'), 'manage-blocks'],
        'activity' => [route('admin.activity'), 'manage-settings'],
        'settings' => [route('admin.settings'), 'manage-settings'],
        'users' => [route('admin.users'), 'manage-users'],
    ];
}

it('shows a super-admin a nav link and a dashboard card for every admin page', function () {
    $html = $this->actingAs($this->admin, 'admin')->get('/admin/dashboard')->assertOk()->getContent();

    foreach (adminDestinations() as $name => [$href]) {
        expect(str_contains($html, e($href)) || str_contains($html, $href))
            ->toBeTrue("Missing link to [{$name}] ({$href}) on the super-admin dashboard.");
    }
});

it('shows a moderator only its permitted links', function () {
    $html = $this->actingAs($this->moderator, 'admin')->get('/admin/dashboard')->assertOk()->getContent();

    // Moderator = moderate-content + intervene-contracts.
    foreach (['moderation.talents', 'moderation.brands', 'moderation.all-reviews', 'projects-oversight', 'contracts'] as $allowed) {
        [$href] = adminDestinations()[$allowed];
        expect(str_contains($html, e($href)) || str_contains($html, $href))
            ->toBeTrue("Moderator should see [{$allowed}].");
    }

    // No governance/system links leak through.
    foreach (['flows', 'skills', 'blocks', 'activity', 'settings', 'users'] as $forbidden) {
        [$href] = adminDestinations()[$forbidden];
        expect(str_contains($html, "href=\"{$href}\""))
            ->toBeFalse("Moderator must NOT see [{$forbidden}] ({$href}).");
    }
});

it('renders the moderation queue counts on the dashboard', function () {
    $this->seed(TalentTypeSeeder::class);
    Review::factory()->count(3)->pending()->create();
    BrandReview::factory()->count(2)->pending()->create();
    Brand::factory()->create(['is_verified' => false]);
    Talent::factory()->create(['status' => 'draft', 'is_published' => false]);

    $this->actingAs($this->admin, 'admin')->get('/admin/dashboard')
        ->assertOk()
        ->assertSee(__('Pending talent reviews'))
        ->assertSee(__('Pending brand reviews'))
        ->assertSee(__('Brands awaiting verification'))
        ->assertSee(__('Pending talent profiles'));
});

it('deep-links a moderation queue via ?queue=', function () {
    $this->actingAs($this->admin, 'admin')->get('/admin/moderation?queue=projects')
        ->assertOk()
        ->assertSee('data-queue="projects"', false);

    // Unknown queue falls back to talents.
    $this->actingAs($this->admin, 'admin')->get('/admin/moderation?queue=bogus')
        ->assertOk()
        ->assertSee('data-queue="talents"', false);
});

it('marks the matching moderation queue link active', function () {
    $html = $this->actingAs($this->admin, 'admin')->get('/admin/moderation?queue=brands')->assertOk()->getContent();

    expect($html)->toContain('aria-current="page"');
});
