<?php

namespace App\Models;

use App\Observers\LikeObserver;
use Database\Factories\LikeFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Like — polymorphic on both sides (schema-master §7): `likeable` is a post or
 * comment, `liker` is a talent or brand. The composite unique index is the
 * idempotency guarantee ToggleLike relies on; counter maintenance lives in
 * LikeObserver so it happens inside the writing transaction (ADR-F6).
 */
#[ObservedBy(LikeObserver::class)]
class Like extends Model
{
    /** @use HasFactory<LikeFactory> */
    use HasFactory;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'likeable_type', 'likeable_id', 'liker_type', 'liker_id',
    ];

    /**
     * The liked post or comment.
     *
     * @return MorphTo<Model, $this>
     */
    public function likeable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The liking actor (talent or brand).
     *
     * @return MorphTo<Model, $this>
     */
    public function liker(): MorphTo
    {
        return $this->morphTo();
    }
}
