<?php

use App\Models\Review;
use App\Models\Talent;

beforeEach(fn () => $this->withoutVite());

it('renders the review form for a published talent', function () {
    Talent::factory()->create(['slug' => 'jane']);

    $this->get(route('talent.review.create', ['slug' => 'jane']))->assertOk();
});

it('writes a pending review from the public form', function () {
    $talent = Talent::factory()->create(['slug' => 'jane']);

    $this->postJson(route('talent.review.store', ['slug' => 'jane']), [
        'reviewer_name' => 'Client A',
        'reviewer_role' => 'Art Director',
        'reviewer_company' => 'Studio X',
        'rating' => 5,
        'body' => 'A pleasure to work with — delivered ahead of schedule.',
        'project_type' => 'editorial',
    ])->assertCreated()->assertJsonPath('success', true);

    $review = Review::firstOrFail();
    expect($review->talent_id)->toBe($talent->id);
    expect((bool) $review->is_approved)->toBeFalse();
    expect($review->status->getValue())->toBe('pending');
    expect($review->reviewer_name)->toBe('Client A');
});

it('validates the review (missing rating & body → 422)', function () {
    Talent::factory()->create(['slug' => 'jane']);

    $this->postJson(route('talent.review.store', ['slug' => 'jane']), ['reviewer_name' => 'x'])
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    expect(Review::count())->toBe(0);
});

it('404s when submitting to an unpublished talent', function () {
    Talent::factory()->draft()->create(['slug' => 'hidden']);

    $this->postJson(route('talent.review.store', ['slug' => 'hidden']), [
        'reviewer_name' => 'x', 'rating' => 5, 'body' => 'y',
    ])->assertNotFound();

    expect(Review::count())->toBe(0);
});
