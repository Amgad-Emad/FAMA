<?php

use App\Models\Project;
use App\Models\ProjectCredit;
use App\Models\Talent;
use App\Models\TalentType;
use App\Notifications\Credits\ProjectCreditAccepted;
use App\Notifications\Credits\ProjectCreditRequested;
use App\Services\ProjectCreditService;
use App\Services\SkillsService;
use App\Services\TalentProjectService;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\SpecialitySeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => $this->seed([TalentTypeSeeder::class, SpecialitySeeder::class, BlockTypeSeeder::class]));

/** A published talent who works as the given skill. */
function creditTalent(string $name, string $skill = 'photography'): Talent
{
    $talent = Talent::factory()->create([
        'display_name' => $name,
        'is_published' => true,
        'is_verified' => true,
    ]);

    app(SkillsService::class)->addType($talent, TalentType::where('slug', $skill)->firstOrFail());

    return $talent;
}

/** A project owned by the given talent, tagged with their first skill. */
function creditProject(Talent $owner, string $title = 'AlUla campaign'): Project
{
    return app(TalentProjectService::class)->create(
        $owner,
        ['title' => ['en' => $title]],
        [$owner->talentTypes()->firstOrFail()->id],
    );
}

// ----- The confirmation rule -------------------------------------------------

it('keeps an on-platform credit invisible until the person confirms it', function () {
    $owner = creditTalent('Owner');
    $peer = creditTalent('Peer', 'styling');
    $project = creditProject($owner);

    $credit = app(ProjectCreditService::class)->add($project, [
        'talent_id' => $peer->id,
        'talent_type_id' => $peer->talentTypes()->firstOrFail()->id,
        'role' => 'Stylist',
    ]);

    expect($credit->status->getValue())->toBe('pending');

    // Not on the project page …
    $this->get(route('talent.work', ['slug' => $owner->slug, 'project' => $project->id]))
        ->assertOk()
        ->assertDontSee('Stylist');

    // … and not on the peer's own profile.
    expect($peer->fresh()->creditedProjects)->toHaveCount(0);

    app(ProjectCreditService::class)->accept($credit);

    $this->get(route('talent.work', ['slug' => $owner->slug, 'project' => $project->id]))
        ->assertOk()
        ->assertSee('Stylist');

    expect($peer->fresh()->creditedProjects->pluck('id')->all())->toBe([$project->id]);
});

it('lists an off-platform credit immediately — there is nobody to ask', function () {
    $owner = creditTalent('Owner');
    $project = creditProject($owner);

    $credit = app(ProjectCreditService::class)->add($project, [
        'name' => 'Hana Farouk',
        'talent_type_id' => TalentType::where('slug', 'hair-makeup')->firstOrFail()->id,
        'role' => 'Hair & makeup',
    ]);

    expect($credit->status->getValue())->toBe('listed')
        ->and($credit->isOnPlatform())->toBeFalse();

    $this->get(route('talent.work', ['slug' => $owner->slug, 'project' => $project->id]))
        ->assertOk()
        ->assertSee('Hana Farouk');
});

it('removes a declined credit from both profiles, even after it was accepted', function () {
    $owner = creditTalent('Owner');
    $peer = creditTalent('Peer', 'styling');
    $project = creditProject($owner);
    $service = app(ProjectCreditService::class);

    $credit = $service->add($project, [
        'talent_id' => $peer->id,
        'talent_type_id' => $peer->talentTypes()->firstOrFail()->id,
        'role' => 'Stylist',
    ]);
    $service->accept($credit);

    expect($peer->fresh()->creditedProjects)->toHaveCount(1);

    // Withdrawing is the same transition as refusing: being publicly associated
    // with a project has to stay the collaborator's call.
    $service->decline($credit->fresh());

    expect($peer->fresh()->creditedProjects)->toHaveCount(0);

    $this->get(route('talent.work', ['slug' => $owner->slug, 'project' => $project->id]))
        ->assertOk()
        ->assertDontSee('Stylist');
});

it('refuses to credit the project’s own owner', function () {
    $owner = creditTalent('Owner');
    $project = creditProject($owner);

    expect(fn () => app(ProjectCreditService::class)->add($project, [
        'talent_id' => $owner->id,
        'talent_type_id' => $owner->talentTypes()->firstOrFail()->id,
        'role' => 'Photographer',
    ]))->toThrow(InvalidArgumentException::class);
});

it('refuses to credit the same person twice on one project', function () {
    $owner = creditTalent('Owner');
    $peer = creditTalent('Peer', 'styling');
    $project = creditProject($owner);
    $service = app(ProjectCreditService::class);
    $data = [
        'talent_id' => $peer->id,
        'talent_type_id' => $peer->talentTypes()->firstOrFail()->id,
        'role' => 'Stylist',
    ];

    $service->add($project, $data);

    expect(fn () => $service->add($project, $data))->toThrow(InvalidArgumentException::class);
});

// ----- Authorisation ---------------------------------------------------------

it('never lets the project owner answer on the collaborator’s behalf', function () {
    $owner = creditTalent('Owner');
    $peer = creditTalent('Peer', 'styling');
    $project = creditProject($owner);

    $credit = app(ProjectCreditService::class)->add($project, [
        'talent_id' => $peer->id,
        'talent_type_id' => $peer->talentTypes()->firstOrFail()->id,
        'role' => 'Stylist',
    ]);

    // Owning the project is a different question from being the person named.
    $this->actingAs($owner, 'talent')
        ->patchJson(route('talent.credits.respond', [$credit->id, 'accept']))
        ->assertStatus(403);

    expect($credit->fresh()->status->getValue())->toBe('pending');

    $this->actingAs($peer, 'talent')
        ->patchJson(route('talent.credits.respond', [$credit->id, 'accept']))
        ->assertOk();

    expect($credit->fresh()->status->getValue())->toBe('accepted');
});

it('never lets a stranger add a credit to someone else’s project', function () {
    $owner = creditTalent('Owner');
    $stranger = creditTalent('Stranger', 'styling');
    $project = creditProject($owner);

    $this->actingAs($stranger, 'talent')->postJson(route('talent.projects.credits.store', $project->id), [
        'name' => 'Anyone',
        'talent_type_id' => TalentType::where('slug', 'styling')->firstOrFail()->id,
        'role' => 'Stylist',
    ])->assertStatus(403);

    expect(ProjectCredit::count())->toBe(0);
});

// ----- Notifications ---------------------------------------------------------

it('notifies the collaborator on request and the owner on the answer', function () {
    Notification::fake();

    $owner = creditTalent('Owner');
    $peer = creditTalent('Peer', 'styling');
    $project = creditProject($owner);
    $service = app(ProjectCreditService::class);

    $credit = $service->add($project, [
        'talent_id' => $peer->id,
        'talent_type_id' => $peer->talentTypes()->firstOrFail()->id,
        'role' => 'Stylist',
    ]);
    $service->notifyRequested($credit);

    Notification::assertSentTo($peer, ProjectCreditRequested::class);

    $service->accept($credit);

    Notification::assertSentTo($owner, ProjectCreditAccepted::class);
});

// ----- The credited person's portfolio ---------------------------------------

it('files a credited project under the skill it was credited in, not the project’s own', function () {
    $owner = creditTalent('Owner');                   // photography
    $peer = creditTalent('Peer', 'styling');          // styling
    $project = creditProject($owner);                 // a PHOTOGRAPHY project

    $credit = app(ProjectCreditService::class)->add($project, [
        'talent_id' => $peer->id,
        'talent_type_id' => $peer->talentTypes()->firstOrFail()->id, // credited as a STYLIST
        'role' => 'Stylist',
    ]);
    app(ProjectCreditService::class)->accept($credit);

    $peer = $peer->fresh()->load(['projects.talentTypes', 'creditedProjects']);
    $styling = TalentType::where('slug', 'styling')->firstOrFail();
    $photography = TalentType::where('slug', 'photography')->firstOrFail();

    expect($peer->portfolioProjects($styling->id)->pluck('id')->all())->toBe([$project->id])
        ->and($peer->portfolioProjects($photography->id)->pluck('id')->all())->toBe([]);
});

it('shows a project once when the talent both owns it and is credited on it', function () {
    $owner = creditTalent('Owner');
    $project = creditProject($owner);

    // Written straight to the table: the service refuses this, but a legacy or
    // hand-made row must not double the project on the profile.
    ProjectCredit::factory()->accepted()->create([
        'project_id' => $project->id,
        'talent_id' => $owner->id,
        'talent_type_id' => $owner->talentTypes()->firstOrFail()->id,
    ]);

    $owner = $owner->fresh()->load(['projects.talentTypes', 'creditedProjects']);

    expect($owner->portfolioProjects($owner->talentTypes()->firstOrFail()->id))->toHaveCount(1);
});

it('makes confirmed work actually appear, even in a skill that seeds no projects block', function () {
    // Photography's default_blocks are gallery/showreel/equipment/… — NO
    // projects block. Without the accept-time fix, confirming a credit here
    // would put the project in a tab that renders nothing.
    $owner = creditTalent('Owner');
    $peer = creditTalent('Peer', 'photography');
    $project = creditProject($owner, 'Nomad launch');

    $photography = TalentType::where('slug', 'photography')->firstOrFail();

    expect($peer->profileBlocks()->where('talent_type_id', $photography->id)
        ->whereRelation('blockType', 'key', 'projects')->exists())->toBeFalse();

    $credit = app(ProjectCreditService::class)->add($project, [
        'talent_id' => $peer->id,
        'talent_type_id' => $photography->id,
        'role' => 'Second shooter',
    ]);
    app(ProjectCreditService::class)->accept($credit);

    expect($peer->fresh()->profileBlocks()->where('talent_type_id', $photography->id)
        ->whereRelation('blockType', 'key', 'projects')->exists())->toBeTrue();

    $this->get(url($peer->slug))->assertOk()->assertSee('Nomad launch');
});

it('files credited work in the universal region when the credit names a skill they do not hold', function () {
    $owner = creditTalent('Owner');
    $peer = creditTalent('Peer', 'styling');
    $project = creditProject($owner, 'Unheld skill work');

    // The OWNER picks the credit's skill, and it need not be one the peer lists.
    $unheld = TalentType::where('slug', 'retouching-post')->firstOrFail();

    $credit = app(ProjectCreditService::class)->add($project, [
        'talent_id' => $peer->id,
        'talent_type_id' => $unheld->id,
        'role' => 'Retoucher',
    ]);
    app(ProjectCreditService::class)->accept($credit);

    $peer = $peer->fresh()->load(['talentTypes', 'projects.talentTypes', 'creditedProjects']);

    // Nowhere to file it under, so it lands in the universal region rather than
    // vanishing after they confirmed it.
    expect($peer->portfolioProjects(null)->pluck('id')->all())->toContain($project->id);

    $this->get(url($peer->slug))->assertOk()->assertSee('Unheld skill work');
});

// ----- Date: recorded, not displayed -----------------------------------------

it('records the date, keeps year in sync, and shows the year as the tag', function () {
    $owner = creditTalent('Owner');

    $this->actingAs($owner, 'talent')->postJson(route('talent.projects.store'), [
        'title' => ['en' => 'Dated work'],
        'skill_ids' => [$owner->talentTypes()->firstOrFail()->id],
        'occurred_on' => '2023-04-18',
    ])->assertCreated();

    $project = Project::firstOrFail();

    expect($project->occurred_on->toDateString())->toBe('2023-04-18')
        // `year` is a derived projection kept for existing readers.
        ->and($project->year)->toBe(2023);

    // 2026-11-04 (co-founder prototype, supersedes ADR-AA's "never displayed"):
    // the YEAR shows as the tag; the full date still appears nowhere.
    $this->get(route('talent.work', ['slug' => $owner->slug, 'project' => $project->id]))
        ->assertOk()
        ->assertSee('<span class="ytag">2023</span>', false)
        ->assertDontSee('18 Apr')
        ->assertDontSee('2023-04-18');
});

// ----- Client: hidden when empty ---------------------------------------------

it('renders no client label at all for self-initiated work', function () {
    $owner = creditTalent('Owner');

    $withClient = app(TalentProjectService::class)->create($owner,
        ['title' => ['en' => 'Commissioned'], 'client_name' => 'Nefertari'],
        [$owner->talentTypes()->firstOrFail()->id]);

    $selfInitiated = app(TalentProjectService::class)->create($owner,
        ['title' => ['en' => 'Personal test']],
        [$owner->talentTypes()->firstOrFail()->id]);

    $this->get(route('talent.work', ['slug' => $owner->slug, 'project' => $withClient->id]))
        ->assertOk()->assertSee('Nefertari');

    // No placeholder, no empty label — the absence says it.
    $this->get(route('talent.work', ['slug' => $owner->slug, 'project' => $selfInitiated->id]))
        ->assertOk()
        ->assertDontSee('personal project', false)
        ->assertSee('Personal test');
});

// ----- The work: an ordered gallery ------------------------------------------

it('appends uploads to the end and lets the owner choose the lead frame', function () {
    Storage::fake('public');

    $owner = creditTalent('Owner');
    $project = creditProject($owner);

    $this->actingAs($owner, 'talent')->postJson(route('talent.projects.media.store', $project->id), [
        'files' => [
            UploadedFile::fake()->image('first.jpg'),
            UploadedFile::fake()->image('second.jpg'),
        ],
    ])->assertOk();

    $media = $project->fresh()->orderedMedia();

    expect($media)->toHaveCount(2)
        ->and($media->first()->file_name)->toBe('first.jpg');

    // Reordering is the edit — the first frame is chosen, not defaulted.
    $this->actingAs($owner, 'talent')->patchJson(route('talent.projects.media.reorder', $project->id), [
        'order' => $media->pluck('id')->reverse()->values()->all(),
    ])->assertOk();

    expect($project->fresh()->orderedMedia()->first()->file_name)->toBe('second.jpg');
});

it('refuses to reorder media that is not on the project', function () {
    Storage::fake('public');

    $owner = creditTalent('Owner');
    $mine = creditProject($owner, 'Mine');
    $other = creditProject($owner, 'Other');

    app(TalentProjectService::class)->addMedia($other, [UploadedFile::fake()->image('theirs.jpg')]);
    $foreignId = $other->fresh()->orderedMedia()->first()->id;

    $this->actingAs($owner, 'talent')
        ->patchJson(route('talent.projects.media.reorder', $mine->id), ['order' => [$foreignId]])
        ->assertStatus(422);
});
