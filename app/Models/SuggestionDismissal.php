<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * SuggestionDismissal (5Z) — an actor's permanent "not this one" on a
 * follow suggestion. Append-only; the unique index makes re-dismissal a
 * no-op.
 */
class SuggestionDismissal extends Model
{
    public $timestamps = false;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'actor_type', 'actor_id', 'suggestible_type', 'suggestible_id', 'dismissed_at',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dismissed_at' => 'datetime',
        ];
    }

    /**
     * The dismissing actor.
     *
     * @return MorphTo<Model, $this>
     */
    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The dismissed talent or brand.
     *
     * @return MorphTo<Model, $this>
     */
    public function suggestible(): MorphTo
    {
        return $this->morphTo();
    }
}
