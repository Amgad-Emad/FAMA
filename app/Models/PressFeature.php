<?php

namespace App\Models;

use Database\Factories\PressFeatureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * PressFeature — a media mention (schema-master §2). `url` is the external
 * article link; the thumbnail is an uploaded asset. `title`/`publication` are
 * kept as published (not translatable).
 */
class PressFeature extends Model implements HasMedia
{
    /** @use HasFactory<PressFeatureFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = ['talent_id', 'publication', 'title', 'url', 'published_date', 'position'];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_date' => 'date',
            'position' => 'integer',
        ];
    }

    /**
     * Single uploaded press thumbnail.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail')->singleFile();
    }

    /**
     * Card-sized thumbnail conversion.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(400)->height(400)->nonQueued();
    }

    /**
     * Press thumbnail URL from the media library (null if none).
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        $this->loadMissing('media');

        return $this->getFirstMediaUrl('thumbnail') ?: null;
    }

    /**
     * Owning talent.
     *
     * @return BelongsTo<Talent, $this>
     */
    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }
}
