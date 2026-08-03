<?php

namespace App\Models;

use App\Observers\BookmarkObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Bookmark (5X) — polymorphic both sides: who saved (`bookmarker` =
 * talent/brand) and what (`bookmarkable` — posts only this phase; talents and
 * Projects are a policy flip away, ADR-F9). Append-only; the unique index is
 * the idempotency guarantee. Counters move via BookmarkObserver (ADR-F6).
 */
#[ObservedBy(BookmarkObserver::class)]
class Bookmark extends Model
{
    use HasFactory;

    public const ?string UPDATED_AT = null;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'bookmarker_type', 'bookmarker_id', 'bookmarkable_type', 'bookmarkable_id', 'created_at',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * The saving actor.
     *
     * @return MorphTo<Model, $this>
     */
    public function bookmarker(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The saved thing (posts this phase).
     *
     * @return MorphTo<Model, $this>
     */
    public function bookmarkable(): MorphTo
    {
        return $this->morphTo();
    }
}
