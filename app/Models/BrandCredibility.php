<?php

namespace App\Models;

use Database\Factories\BrandCredibilityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Brand credibility (schema-master §4) — 1:1 denormalized trust counters, read
 * cheaply on the profile. Monotonic project count; `brief_quality_score` is
 * internal. Recalculated by events (Phase 2B), not live.
 */
class BrandCredibility extends Model
{
    /** @use HasFactory<BrandCredibilityFactory> */
    use HasFactory;

    protected $table = 'brand_credibility';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'brand_id', 'completed_projects_count', 'avg_response_time_hours',
        'response_rate_pct', 'brief_quality_score',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_projects_count' => 'integer',
            'avg_response_time_hours' => 'decimal:2',
            'response_rate_pct' => 'integer',
            'brief_quality_score' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
