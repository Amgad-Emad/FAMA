<?php

use App\Models\Talent;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TalentTypeSeeder;

/** Admin dashboard insights: series over a range, totals vs the previous period, funnel. */
beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, TalentTypeSeeder::class]);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

it('renders the insights, funnel and skills mix on the dashboard', function () {
    $this->actingAs($this->admin, 'admin')->get('/admin/dashboard')
        ->assertOk()
        ->assertSee('adminInsights(', false)
        ->assertSee(__('Road to Live'))
        ->assertSee(__('Skills mix'));
});

it('counts new talents per bucket and against the previous period', function () {
    Talent::factory()->count(2)->create(['created_at' => now()->subDays(2)]);
    Talent::factory()->create(['created_at' => now()->subDays(10)]); // previous 7-day window

    $data = $this->actingAs($this->admin, 'admin')
        ->getJson('/admin/dashboard/insights?range=7d')->assertOk()->json('data');

    expect($data['bucket'])->toBe('day')
        ->and($data['labels'])->toHaveCount(7)
        ->and(array_sum($data['series']['signups']))->toBe(2)
        ->and($data['totals']['signups']['previous'])->toBe(1)
        ->and($data['totals']['signups']['delta_pct'])->toBe(100);
});

it('buckets 90 days by week and 12 months by month', function () {
    $this->actingAs($this->admin, 'admin');

    expect($this->getJson('/admin/dashboard/insights?range=90d')->json('data.bucket'))->toBe('week')
        ->and($this->getJson('/admin/dashboard/insights?range=12m')->json('data.labels'))->toHaveCount(12);
});

it('rejects an unknown range', function () {
    $this->actingAs($this->admin, 'admin')->getJson('/admin/dashboard/insights?range=5y')->assertUnprocessable();
});
