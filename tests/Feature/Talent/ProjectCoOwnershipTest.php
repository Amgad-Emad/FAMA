<?php

use App\Models\Project;
use App\Models\ProjectCredit;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\ProjectCreditService;
use App\Services\SkillsService;
use App\Services\TalentProjectService;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\SpecialitySeeder;
use Database\Seeders\TalentTypeSeeder;

/**
 * Co-ownership (2026-10-12): confirming a credit makes you a co-owner. Any
 * owner can edit or leave; only a sole owner can delete.
 */
beforeEach(fn () => $this->seed([TalentTypeSeeder::class, SpecialitySeeder::class, BlockTypeSeeder::class]));

/** A published talent holding the given skills (first = primary). */
function coTalent(string $name, array $skills = ['photography']): Talent
{
    $talent = Talent::factory()->create(['display_name' => $name, 'is_published' => true, 'is_verified' => true]);
    foreach ($skills as $slug) {
        app(SkillsService::class)->addType($talent, TalentType::where('slug', $slug)->firstOrFail());
    }

    return $talent;
}

/** Credit $peer on $project in $skill and have them accept it. */
function coAccept(Project $project, Talent $peer, string $skill = 'styling'): ProjectCredit
{
    $credit = app(ProjectCreditService::class)->add($project, [
        'talent_id' => $peer->id,
        'talent_type_id' => TalentType::where('slug', $skill)->value('id'),
        'role' => 'Stylist',
    ]);

    return app(ProjectCreditService::class)->accept($credit);
}

/** A project owned by $owner tagged with their first skill. */
function coProject(Talent $owner, string $title = 'Desert Light'): Project
{
    return app(TalentProjectService::class)->create($owner, ['title' => ['en' => $title]], [$owner->talentTypes()->firstOrFail()->id]);
}

it('lets an accepted co-owner open the editor and use every write endpoint', function () {
    $owner = coTalent('Lina');
    $peer = coTalent('Omar', ['styling']);
    $project = coProject($owner);
    coAccept($project, $peer);

    expect($project->fresh()->isOwnedBy($peer))->toBeTrue()
        ->and($project->fresh()->ownerIds())->toBe([$owner->id, $peer->id]);

    $this->actingAs($peer, 'talent')->get(route('talent.projects.edit', $project))->assertOk();

    // Tags stay the HOME owner's skills (ADR-X), even when a co-owner edits.
    $this->actingAs($peer, 'talent')->patchJson(route('talent.projects.update', $project), [
        'title' => ['en' => 'Desert Light — SS26'],
        'skill_ids' => [$owner->talentTypes()->first()->id],
    ])->assertOk();
    expect($project->fresh()->title)->toBe('Desert Light — SS26');

    $this->actingAs($peer, 'talent')->postJson(route('talent.projects.credits.store', $project), [
        'name' => 'Hana Mostafa', 'talent_type_id' => TalentType::where('slug', 'direction')->value('id'), 'role' => 'Director',
    ])->assertCreated();
});

it('refuses a talent who does not own the project', function () {
    $owner = coTalent('Lina');
    $pending = coTalent('Rania', ['styling']);
    $stranger = coTalent('Karim');
    $project = coProject($owner);

    // A PENDING credit is not ownership.
    app(ProjectCreditService::class)->add($project, ['talent_id' => $pending->id, 'talent_type_id' => $pending->talentTypes()->first()->id, 'role' => 'Stylist']);

    foreach ([$pending, $stranger] as $who) {
        $this->actingAs($who, 'talent')->get(route('talent.projects.edit', $project))->assertForbidden();
        $this->actingAs($who, 'talent')->patchJson(route('talent.projects.update', $project), [
            'title' => ['en' => 'Hijack'], 'skill_ids' => [$owner->talentTypes()->first()->id],
        ])->assertForbidden();
        $this->actingAs($who, 'talent')->deleteJson(route('talent.projects.destroy', $project))->assertForbidden();
        $this->actingAs($who, 'talent')->postJson(route('talent.projects.leave', $project))->assertForbidden();
    }
});

it('lets a co-owner leave, after which they lose access', function () {
    $owner = coTalent('Lina');
    $peer = coTalent('Omar', ['styling']);
    $project = coProject($owner);
    $credit = coAccept($project, $peer);

    $this->actingAs($peer, 'talent')->postJson(route('talent.projects.leave', $project))->assertOk();

    expect($credit->fresh()->status->getValue())->toBe('declined')
        ->and($project->fresh()->talent_id)->toBe($owner->id)
        ->and($project->fresh()->isOwnedBy($peer))->toBeFalse();

    $this->actingAs($peer, 'talent')->get(route('talent.projects.edit', $project))->assertForbidden();
});

it('transfers the project to the longest-standing co-owner when the creator leaves', function () {
    $owner = coTalent('Lina', ['photography']);
    $first = coTalent('Omar', ['styling', 'photography']);
    $second = coTalent('Rania', ['styling']);
    $project = coProject($owner);

    $this->travel(-2)->days();
    $firstCredit = coAccept($project, $first);
    $this->travelBack();
    coAccept($project, $second);

    $this->actingAs($owner, 'talent')->postJson(route('talent.projects.leave', $project))
        ->assertOk()
        ->assertJsonPath('data.owner', 'Omar');

    $project->refresh();
    $photography = TalentType::where('slug', 'photography')->value('id');

    expect($project->talent_id)->toBe($first->id)
        // The tag the heir holds survives (ADR-X: tags are the home owner's).
        ->and($project->skillIds())->toBe([$photography])
        // Their credit row is kept as their credit line, but never as "co-owner of themselves".
        ->and($firstCredit->fresh()->status->getValue())->toBe('accepted')
        ->and($project->ownerIds())->toBe([$first->id, $second->id])
        ->and($project->isOwnedBy($owner))->toBeFalse();

    $this->actingAs($owner, 'talent')->get(route('talent.projects.edit', $project))->assertForbidden();
});

it('re-homes the tags onto a skill the heir holds when none of the old tags survive', function () {
    $owner = coTalent('Lina', ['photography']);
    $heir = coTalent('Omar', ['styling']);
    $project = coProject($owner);
    coAccept($project, $heir, 'styling');

    app(TalentProjectService::class)->leave($project->fresh(), $owner);

    expect($project->fresh()->skillIds())->toBe([TalentType::where('slug', 'styling')->value('id')]);
});

it('lets a sole owner delete', function () {
    $owner = coTalent('Lina');
    $project = coProject($owner);

    $this->actingAs($owner, 'talent')->deleteJson(route('talent.projects.destroy', $project))->assertOk();

    expect(Project::find($project->id))->toBeNull();
});

it('refuses to delete while other owners exist', function () {
    $owner = coTalent('Lina');
    $peer = coTalent('Omar', ['styling']);
    $project = coProject($owner);
    coAccept($project, $peer);

    foreach ([$owner, $peer] as $who) {
        $this->actingAs($who, 'talent')->deleteJson(route('talent.projects.destroy', $project))
            ->assertStatus(422)
            ->assertJsonPath('message', 'This project has other owners. Leave it instead.');
    }

    expect(Project::find($project->id))->not->toBeNull();
});

it('does not let the sole creator leave', function () {
    $owner = coTalent('Lina');
    $project = coProject($owner);

    $this->actingAs($owner, 'talent')->postJson(route('talent.projects.leave', $project))->assertStatus(422);

    expect($project->fresh()->talent_id)->toBe($owner->id);
});

it('flags co-owned projects in the list payload so the UI offers Leave instead of Delete', function () {
    $owner = coTalent('Lina');
    $peer = coTalent('Omar', ['styling']);
    $shared = coProject($owner, 'Shared');
    coProject($owner, 'Solo');
    coAccept($shared, $peer);

    $rows = collect($this->actingAs($owner, 'talent')->getJson(route('talent.projects.data'))->assertOk()->json('data'))->keyBy('label');

    expect($rows['Shared']['has_co_owners'])->toBeTrue()
        ->and($rows['Solo']['has_co_owners'])->toBeFalse();
});
