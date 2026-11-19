<?php

use App\Models\Project;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\SkillsService;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Support\Facades\Cache;

/**
 * SEO (ADR-AO): robots.txt, the sitemaps, llms.txt, every public page's
 * description / canonical / share card / structured data, and the robots
 * allowlist — signed-in pages and private variants never reach search.
 */
beforeEach(function () {
    Cache::flush();
    config(['seo.indexable' => true]);
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);

    $this->live = Talent::factory()->create(['is_published' => true, 'is_verified' => true, 'display_name' => 'Lina Haddad', 'base_city' => 'Cairo', 'base_country' => 'Egypt']);
    app(SkillsService::class)->addType($this->live, TalentType::where('slug', 'photography')->firstOrFail());
    $this->private = Talent::factory()->create(['is_published' => false, 'display_name' => 'Hidden Person']);

    $this->project = Project::factory()->for($this->live)->create([
        'talent_type_id' => $this->live->talentTypes()->first()->id,
        'title' => ['en' => 'Desert Light'], 'client_name' => 'Maison Nour',
        'blocks' => [['t' => 'text', 'v' => 'A resort capsule shot at golden hour.']],
    ]);
    $this->hiddenProject = Project::factory()->for($this->private)->create(['title' => ['en' => 'Secret Work']]);
});

it('points crawlers at the sitemap and keeps them out of signed-in areas', function () {
    $this->get('/robots.txt')->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Disallow: /talent/', false)
        ->assertSee('Disallow: /admin/', false)
        ->assertSee('Sitemap: '.route('seo.sitemap'), false);

    // a staging / local copy never competes with the real site
    config(['seo.indexable' => false]);
    expect($this->get('/robots.txt')->getContent())->toBe("User-agent: *\nDisallow: /\n");
});

it('lists every public profile and project in the sitemap, and nothing private', function () {
    $this->get('/sitemap.xml')->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee(route('seo.sitemap.file', ['file' => 'pages']), false)
        ->assertSee(route('seo.sitemap.file', ['file' => 'profiles-1']), false)
        ->assertSee(route('seo.sitemap.file', ['file' => 'projects-1']), false);

    $this->get('/sitemaps/profiles-1.xml')->assertOk()
        ->assertSee(route('talent.public', ['slug' => $this->live->slug]), false)
        ->assertDontSee('/'.$this->private->slug.'<', false);

    $this->get('/sitemaps/projects-1.xml')->assertOk()
        ->assertSee(route('talent.work', ['slug' => $this->live->slug, 'project' => $this->project->id]), false)
        ->assertDontSee('/work/'.$this->hiddenProject->id.'<', false);

    $this->get('/sitemaps/pages.xml')->assertOk()->assertSee(route('feed'), false);
    $this->get('/sitemaps/profiles-9.xml')->assertNotFound();
    $this->get('/sitemaps/nonsense.xml')->assertNotFound();
});

it('pages the sitemap when there are more profiles than one file holds', function () {
    config(['seo.sitemap.per_page' => 1]);
    Talent::factory()->create(['is_published' => true]);

    $this->get('/sitemap.xml')->assertSee('profiles-2.xml', false)->assertDontSee('profiles-3.xml', false);
    $this->get('/sitemaps/profiles-2.xml')->assertOk();
});

it('explains the site to AI assistants in llms.txt, naming only public profiles', function () {
    $this->get('/llms.txt')->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('# Fama', false)
        ->assertSee('[Lina Haddad]('.route('talent.public', ['slug' => $this->live->slug]).')', false)
        ->assertSee('Cairo, Egypt', false)
        ->assertDontSee('Hidden Person')
        ->assertSee(route('seo.sitemap'), false);
});

it('gives a public profile its description, canonical, share card and Person data', function () {
    $html = $this->get(route('talent.public', ['slug' => $this->live->slug]).'?skill=photography')->assertOk()
        ->assertHeaderMissing('X-Robots-Tag')
        ->getContent();

    expect($html)
        ->toContain('<meta name="robots" content="index, follow, max-image-preview:large">')
        ->toContain('<link rel="canonical" href="'.route('talent.public', ['slug' => $this->live->slug]).'">')   // no ?skill
        ->toContain('<meta property="og:type" content="profile">')
        ->toContain('Lina Haddad — Photography in Cairo, Egypt.')
        ->toContain('"@type":"ProfilePage"')
        ->toContain('"@type":"Person"')
        ->toContain('"addressLocality":"Cairo"');
});

it('keeps a private profile out of search even for its owner', function () {
    $this->actingAs($this->private, 'talent')
        ->get(route('talent.public', ['slug' => $this->private->slug]))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

it('gives a project page CreativeWork data with the team, and never names a confidential client', function () {
    $html = $this->get(route('talent.work', ['slug' => $this->live->slug, 'project' => $this->project]))->assertOk()->getContent();
    expect($html)
        ->toContain('<meta name="description" content="A resort capsule shot at golden hour.">')
        ->toContain('<meta property="og:type" content="article">')
        ->toContain('"@type":"CreativeWork"')
        ->toContain('"name":"Lina Haddad"')
        ->toContain('"sourceOrganization":{"@type":"Organization","name":"Maison Nour"}');

    $this->project->update(['client_confidential' => true]);
    $html = $this->get(route('talent.work', ['slug' => $this->live->slug, 'project' => $this->project]))->getContent();
    expect($html)->not->toContain('Maison Nour');
});

it('keeps preview and visitor variants of a project out of search', function () {
    $url = route('talent.work', ['slug' => $this->live->slug, 'project' => $this->project]);

    $this->get($url.'?visitor=1')->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    $this->actingAs($this->live, 'talent')->get($url.'?preview=1')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('marks every signed-in page noindex in a header too', function () {
    $this->actingAs($this->live, 'talent')->get(route('talent.projects'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

it('carries the site-wide Organization and WebSite data on the feed', function () {
    $this->get(route('feed'))->assertOk()
        ->assertSee('"@type":"Organization"', false)
        ->assertSee('"@type":"WebSite"', false)
        ->assertSee('<link rel="canonical" href="'.route('feed').'">', false);
});
