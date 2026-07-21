<?php

namespace App\Models;

use Database\Factories\BrandCreativeNeedFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Brand creative needs (schema-master §4) — 1:1; drives the discovery feed and
 * brief pre-fill. ADR-6: `talent_types` and `project_types` are promoted to
 * pivots; `budget_tier` (internal) and `project_frequency` stay columns.
 */
class BrandCreativeNeed extends Model
{
    /** @use HasFactory<BrandCreativeNeedFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['brand_id', 'project_frequency', 'budget_tier'];

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Skills this brand hires ("all brands needing photographers").
     *
     * @return BelongsToMany<TalentType, $this>
     */
    public function talentTypes(): BelongsToMany
    {
        return $this->belongsToMany(TalentType::class, 'brand_creative_need_talent_type', 'brand_creative_need_id', 'talent_type_id')
            ->withTimestamps();
    }

    /** @return HasMany<BrandProjectType, $this> */
    public function projectTypes(): HasMany
    {
        return $this->hasMany(BrandProjectType::class);
    }
}
