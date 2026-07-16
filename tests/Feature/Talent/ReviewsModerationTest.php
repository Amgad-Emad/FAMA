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
