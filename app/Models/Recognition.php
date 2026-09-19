<?php

namespace App\Models;

use App\States\Recognition\RecognitionState;
use Database\Factories\RecognitionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStates\HasStates;

/**
 * Recognition — a peer vouch (ADR-Z).
 *
 * The sibling of Review, and the difference is entirely in WHO WRITES IT:
 *   Review       BRAND  → talent, after a booking completes on Fama. Verified
 *                because the booking happened here — and therefore impossible
 *                at launch, when no booking has completed yet.
 *   Recognition  TALENT → talent, invited rather than earned. Needs no booking,
 *                which is precisely why it works from day one.
 *
 * `body` is NOT translatable, for the same reason a review's is not: testimony
 * is kept in the language it was written. The author is always a talent on the
 * platform, so the vouch carries a real, clickable person.
 */
class Recognition extends Model
{
    /** @use HasFactory<RecognitionFactory> */
    use HasFactory, HasStates, SoftDeletes;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'talent_id', 'author_talent_id', 'relationship', 'body',
        'status', 'is_approved', 'recognized_at',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'recognized_at' => 'datetime',
            'status' => RecognitionState::class,
        ];
    }

    /**
     * The talent being vouched for.
     *
     * @return BelongsTo<Talent, $this>
     */
    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    /**
     * The peer who wrote it.
     *
     * @return BelongsTo<Talent, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Talent::class, 'author_talent_id');
    }

    /**
     * Only the vouches cleared for public display.
     *
     * @param  Builder<Recognition>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('is_approved', true);
    }
}
