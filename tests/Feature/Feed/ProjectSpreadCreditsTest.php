<?php

use App\Actions\Feed\ShareProjectAsPost;
use App\Actions\Feed\SyncMentions;
use App\Models\Post;
use App\Models\Project;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\RichTextParser;
use Illuminate\Support\Facades\DB;

// ----- 5W: the widened snapshot ----------------------------------------------

it('freezes the widened snapshot and renders a complete dead frame', function () {
    $talent = Talent::factory()->create();
    $skill = TalentType::query()->first() ?? TalentType::factory()->create();
    $talent->talentTypes()->attach($skill->id, ['is_primary' => true, 'position' => 0]);

    $project = Project::factory()->create([
        'talent_id' => $talent->id,
        'title' => ['en' => 'Desert Editorial'],
        'client_name' => 'Azza Fahmy',
        'occurred_on' => '2026-05-04',
    ]);

    $post = app(ShareProjectAsPost::class)($talent, $project, null, [$skill->id]);

    expect($post->shared_snapshot['client_name'])->toBe('Azza Fahmy')
        ->and($post->shared_snapshot['year'])->toBe(2026)
        ->and($post->shared_snapshot['skill_ids'])->toBe([$skill->id])
        ->and($post->shared_snapshot)->toHaveKey('media_count');

    // Kill the Project — the frame stays complete, marked unavailable.
    $project->delete();

    $html = $this->getJson("/posts/{$post->id}/fragment")->assertOk()->json('data.html');

    expect($html)->toContain('Desert Editorial')
        ->toContain('Azza Fahmy')
        ->toContain('2026')
        ->toContain(__('This Project is no longer available.'))
        ->not->toContain(__('VIEW THE PROJECT'));
});

it('adds no queries per additional project share on a feed page', function () {
    config(['feed.ranking.per_page' => 20]);
    $talent = Talent::factory()->create(['is_published' => true]);
    $skill = TalentType::query()->first() ?? TalentType::factory()->create();
    $talent->talentTypes()->attach($skill->id, ['is_primary' => true, 'position' => 0]);

    $share = function () use ($talent, $skill) {
        $project = Project::factory()->create(['talent_id' => $talent->id]);

        return app(ShareProjectAsPost::class)($talent, $project, null, [$skill->id]);
    };

    $count = function (): int {
        Illuminate\Support\Facades\Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson('/feed/items')->assertOk();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queries;
    };

    $share();
    $small = $count();

    $share();
    $share();
    $share();
    $large = $count();

    expect($large)->toBe($small);
});

// ----- Skill tags + collaborators-beside-name (revised) ----------------------

it('renders skills as card tags and credits collaborators inline as mentions', function () {
    $author = Talent::factory()->create(['slug' => 'darwin-carter', 'display_name' => 'Darwin Carter']);
    $skills = TalentType::query()->limit(2)->get();

    if ($skills->count() < 2) {
        $skills = TalentType::factory()->count(2)->create();
    }

    $author->talentTypes()->attach($skills[0]->id, ['is_primary' => true, 'position' => 0]);
    $author->talentTypes()->attach($skills[1]->id, ['is_primary' => false, 'position' => 1]);

    $friend = Talent::factory()->create(['slug' => 'pattie-collins', 'display_name' => 'Pattie Collins']);

    $post = Post::factory()->byAuthor($author)->create(['body' => 'On set with @pattie-collins']);
    $post->skills()->attach($skills->pluck('id'));
    app(SyncMentions::class)($post, app(RichTextParser::class)->parse($post->body));

    $html = $this->getJson("/posts/{$post->id}/fragment")->assertOk()->json('data.html');

    // Skills render as the card's tag pills (fama-ui .ptags) carrying the skill
    // names. Escape the expectation: a skill name may legitimately carry an
    // ampersand ("Hair & makeup"), which Blade renders as `&amp;`.
    expect($html)->toContain('class="chips ptags"')
        ->toContain('<span class="tag">'.e($skills[0]->name).'</span>')
        ->toContain('<span class="tag">'.e($skills[1]->name).'</span>');

    // The collaborator is credited inline in the text as a mention link to
    // their profile (the fama-ui card has no "with …" header line).
    expect($html)->toContain('class="feed-mention"')
        ->toContain(route('talent.public', ['slug' => 'pattie-collins']))
        ->not->toContain('class="credits"'); // the old credits block is gone
});
