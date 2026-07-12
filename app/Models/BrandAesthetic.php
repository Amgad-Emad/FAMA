<?php

namespace App\Models;

use Database\Factories\BrandAestheticFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Brand aesthetic (schema-master §4) — 1:1 creative direction, the discovery
 * engine's core input. `mood_tags` are promoted to the `brand_mood_tags` pivot
 * (ADR-6); `brand_references` stays free text.
 */
class BrandAesthetic extends Model
{
    /** @use HasFactory<BrandAestheticFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['brand_id', 'brand_references'];

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** @return HasMany<BrandMoodTag, $this> */
    public function moodTags(): HasMany
    {
        return $this->hasMany(BrandMoodTag::class);
    }
}
