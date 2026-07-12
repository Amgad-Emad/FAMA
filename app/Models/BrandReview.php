<?php

namespace App\Models;

use App\States\BrandReview\BrandReviewState;
use Database\Factories\BrandReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\ModelStates\HasStates;

/**
 * Brand review (schema-master §4) — a talent rating a brand on three axes
 * (communication / fairness / creative respect), tied to a completed deal.
 * Mirrors the talent-side review lifecycle; `status` becomes a state machine in
 * Phase 2B with `is_approved` as its projection. `body` is kept in its original
 * language (not translatable), like talent reviews.
 */
class BrandReview extends Model
{
    /** @use HasFactory<BrandReviewFactory> */
    use HasFactory, HasStates;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'brand_id', 'talent_id', 'deal_id', 'communication_rating', 'fairness_rating',
        'creative_respect_rating', 'body', 'is_approved', 'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'communication_rating' => 'integer',
            'fairness_rating' => 'integer',
            'creative_respect_rating' => 'integer',
            'is_approved' => 'boolean',
            'status' => BrandReviewState::class,
        ];
    }

    /**
     * Mean of the three sub-ratings.
     */
    public function getAverageRatingAttribute(): float
    {
        return round(($this->communication_rating + $this->fairness_rating + $this->creative_respect_rating) / 3, 1);
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** @return BelongsTo<Talent, $this> */
    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    /** @return BelongsTo<Deal, $this> */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }
}
