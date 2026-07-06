<?php

use App\Models\CaseStudy;
use App\Models\Talent;

beforeEach(fn () => $this->withoutVite());

it('renders a published talent’s case study', function () {
    $talent = Talent::factory()->create(['slug' => 'jane']);
    $study = CaseStudy::factory()->for($talent)->create(['title' => ['en' => 'Big Campaign']]);

    $this->get(route('talent.work', ['slug' => 'jane', 'caseStudy' => $study->id]))
        ->assertOk()
        ->assertSee('Big Campaign');
});

it('404s for a case study that does not belong to the talent', function () {
    Talent::factory()->create(['slug' => 'a']);
    $other = Talent::factory()->create(['slug' => 'b']);
    $study = CaseStudy::factory()->for($other)->create();

    $this->get(route('talent.work', ['slug' => 'a', 'caseStudy' => $study->id]))->assertNotFound();
});

it('404s for a case study on an unpublished talent', function () {
    $talent = Talent::factory()->draft()->create(['slug' => 'hidden']);
    $study = CaseStudy::factory()->for($talent)->create();

    $this->get(route('talent.work', ['slug' => 'hidden', 'caseStudy' => $study->id]))->assertNotFound();
});
