<?php

use App\Models\Brand;
use App\Models\BrandProject;
use App\Models\Contract;
use App\Models\Project;
use App\Models\Talent;
use Database\Seeders\ContractFlowSeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(TalentTypeSeeder::class);
    $this->seed(ContractFlowSeeder::class);
});

/** An open, public project on a published brand. */
function openProject(): BrandProject
{
    $brand = Brand::factory()->create();

    return BrandProject::factory()->for($brand)->open()->create(['is_public' => true]);
}

it('lists the talent’s own projects for the @-mention picker', function () {
    $talent = Talent::factory()->create();
    Project::factory()->for($talent)->create(['title' => ['en' => 'Coastline Editorial'], 'position' => 0]);
    Project::factory()->for($talent)->create(['title' => ['en' => 'Studio Beauty'], 'position' => 1]);

    $this->actingAs($talent, 'talent')
        ->getJson('/talent/applications/mentions?q=coast')
        ->assertOk()
        ->assertJsonCount(1, 'data.projects')
        ->assertJsonPath('data.projects.0.title', 'Coastline Editorial');
});

it('submits an application: opens a talent-initiated contract + a rich message with attachments', function () {
    Storage::fake('public');
    $project = openProject();
    $talent = Talent::factory()->create();

    $response = $this->actingAs($talent, 'talent')->post(route('talent.applications.store', $project), [
        'brief' => '<p>I shoot warm lifestyle work — see <span class="mention">@Coastline</span>.</p>',
        'attachments' => [UploadedFile::fake()->image('portfolio.png')],
    ])->assertCreated();

    $contract = Contract::where('brand_id', $project->brand_id)
        ->where('talent_id', $talent->id)
        ->where('brand_project_id', $project->id)
        ->first();

    expect($contract)->not->toBeNull();
    expect($contract->initiated_by)->toBe('talent');
    $response->assertJsonPath('data.contract_url', route('talent.contracts.show', $contract));

    $message = $contract->messages()->where('type', 'message')->latest()->first();
    expect((bool) $message->is_rich)->toBeTrue();
    expect($message->body)->toContain('mention')->toContain('warm lifestyle');
    expect($message->getMedia('attachments'))->toHaveCount(1);
});

it('sanitizes the brief — scripts, event handlers, and images are stripped', function () {
    $project = openProject();
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')->post(route('talent.applications.store', $project), [
        'brief' => '<p>Great fit<script>alert(1)</script></p><img src=x onerror=alert(1)><a href="javascript:alert(1)">x</a>',
    ])->assertCreated();

    $body = Contract::first()->messages()->where('type', 'message')->latest()->first()->body;
    expect($body)->toContain('Great fit');
    expect($body)->not->toContain('<script')
        ->not->toContain('onerror')
        ->not->toContain('<img')
        ->not->toContain('javascript:');
});

it('rejects an empty brief', function () {
    $project = openProject();
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')
        ->postJson(route('talent.applications.store', $project), ['brief' => '<p><br></p>'])
        ->assertStatus(422);
});

it('404s applying to a non-public or closed project', function () {
    $talent = Talent::factory()->create();
    $brand = Brand::factory()->create();

    $private = BrandProject::factory()->for($brand)->open()->create(['is_public' => false]);
    $draft = BrandProject::factory()->for($brand)->create(['is_public' => true]); // draft status

    $this->actingAs($talent, 'talent')->postJson(route('talent.applications.store', $private), ['brief' => '<p>hi</p>'])->assertNotFound();
    $this->actingAs($talent, 'talent')->postJson(route('talent.applications.store', $draft), ['brief' => '<p>hi</p>'])->assertNotFound();
});

it('reuses the same contract when applying to the same project again', function () {
    $project = openProject();
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')->post(route('talent.applications.store', $project), ['brief' => '<p>First take.</p>'])->assertCreated();
    $this->actingAs($talent, 'talent')->post(route('talent.applications.store', $project), ['brief' => '<p>Following up.</p>'])->assertCreated();

    expect(Contract::where('brand_project_id', $project->id)->count())->toBe(1);
    expect(Contract::first()->messages()->where('type', 'message')->count())->toBe(2);
});
