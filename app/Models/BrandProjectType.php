<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A project type a brand hires for (ADR-6 pivot on brand_creative_needs) —
 * indexed for discovery ("brands running campaign videos").
 */
class BrandProjectType extends Model
{
    protected $table = 'brand_creative_need_project_type';

    /**
     * @var list<string>
     */
    protected $fillable = ['brand_creative_need_id', 'project_type'];

    /** @return BelongsTo<BrandCreativeNeed, $this> */
    public function creativeNeed(): BelongsTo
    {
        return $this->belongsTo(BrandCreativeNeed::class, 'brand_creative_need_id');
    }
}
