<?php

use App\Models\Recognition;
use App\Models\Talent;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Activitylog\Models\Activity;

/** Moderation bulk actions: delete many (audited soft deletes) and CSV export. */
beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

it('bulk soft-deletes talents through the audited path', function () {
    $talents = Talent::factory()->count(3)->create();

    $this->actingAs($this->admin, 'admin')
        ->postJson('/admin/moderation/bulk/talents/delete', ['ids' => $talents->take(2)->pluck('id')->all()])
        ->assertOk()->assertJsonPath('data.deleted', 2);

    expect(Talent::onlyTrashed()->count())->toBe(2)
        ->and(Talent::count())->toBe(1)
        ->and(Activity::where('description', 'talent.soft_deleted')->count())->toBe(2);
});

it('bulk deletes recognitions with an audit entry each', function () {
    $recs = Recognition::factory()->count(2)->create();

    $this->actingAs($this->admin, 'admin')
        ->postJson('/admin/moderation/bulk/recognitions/delete', ['ids' => $recs->pluck('id')->all()])
        ->assertOk();

    expect(Recognition::count())->toBe(0)
        ->and(Activity::where('description', 'recognition.deleted')->count())->toBe(2);
});

it('exports the selected talents as CSV', function () {
    $a = Talent::factory()->create(['display_name' => 'Alpha One']);
    Talent::factory()->create(['display_name' => 'Beta Two']);

    $csv = $this->actingAs($this->admin, 'admin')
        ->get('/admin/moderation/export/talents?ids='.$a->id)
        ->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->streamedContent();

    expect($csv)->toContain('Alpha One')->not->toContain('Beta Two')->toContain('Username');
});

it('exports everything matching the search when nothing is selected', function () {
    Talent::factory()->create(['display_name' => 'Zed Match']);
    Talent::factory()->create(['display_name' => 'Other']);

    $csv = $this->actingAs($this->admin, 'admin')->get('/admin/moderation/export/talents?q=Zed')->streamedContent();

    expect($csv)->toContain('Zed Match')->not->toContain(',Other,');
});

it('neutralises spreadsheet formulas in exported cells', function () {
    $t = Talent::factory()->create(['display_name' => '=HYPERLINK("x")']);

    $csv = $this->actingAs($this->admin, 'admin')->get('/admin/moderation/export/talents?ids='.$t->id)->streamedContent();

    expect($csv)->toContain("'=HYPERLINK");
});

it('refuses bulk on unsupported queues and without permission', function () {
    $this->actingAs($this->admin, 'admin')->postJson('/admin/moderation/bulk/projects/delete', ['ids' => [1]])->assertNotFound();

    $powerless = User::factory()->create();
    $this->actingAs($powerless, 'admin')->postJson('/admin/moderation/bulk/talents/delete', ['ids' => [1]])->assertForbidden();
});
