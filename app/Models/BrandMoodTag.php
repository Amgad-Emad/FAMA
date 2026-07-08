<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single mood tag on a brand aesthetic (ADR-6 pivot) — indexed for discovery
 * ("brands with an editorial mood").
 */
class BrandMoodTag extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['brand_aesthetic_id', 'tag'];

    /** @return BelongsTo<BrandAesthetic, $this> */
    public function aesthetic(): BelongsTo
    {
        return $this->belongsTo(BrandAesthetic::class, 'brand_aesthetic_id');
    }
}
