<?php

use App\Models\Review;
use App\Models\Talent;

it('moderates reviews and forbids foreign moderation', function () {
    $talent = Talent::factory()->create();
    $review = Review::factory()->pending()->for($talent)->create();

    $this->actingAs($talent, 'talent')->patchJson(route('talent.reviews.approve', $review->id))->assertOk();
    expect($review->fresh()->is_approved)->toBeTrue();

    $this->actingAs($talent, 'talent')->getJson(route('talent.reviews.data', ['status' => 'approved']))
        ->assertOk()
        ->assertJsonPath('meta.pagination.total', 1);

    $intruder = Talent::factory()->create();
    $foreign = Review::factory()->pending()->for($talent)->create();
    $this->actingAs($intruder, 'talent')->patchJson(route('talent.reviews.approve', $foreign->id))->assertForbidden();
});

it('filters the reviews list by rating', function () {
    $talent = Talent::factory()->create();
    Review::factory()->for($talent)->count(2)->create(['rating' => 5]);
    Review::factory()->for($talent)->create(['rating' => 3]);

    $this->actingAs($talent, 'talent')
        ->getJson(route('talent.reviews.data', ['rating' => 5]))
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->actingAs($talent, 'talent')
        ->getJson(route('talent.reviews.data', ['rating' => 3]))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('shows a read-only reviews list without approve/reject controls', function () {
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')->withoutVite()->get(route('talent.reviews'))
        ->assertOk()
        ->assertSee(__('Filter by rating'))
        ->assertDontSee(__('Moderation queue'))
        ->assertDontSee("act(r.id, 'approve')", false);
});
