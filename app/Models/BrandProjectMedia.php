<?php

namespace App\Models;

use Database\Factories\BrandProjectMediaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * Project media (schema-master §5) — one gallery item. Uploaded image/video go
 * through the medialibrary `media` collection (`media_url`/`thumbnail_url`
 * accessors); `embed_url` is external for `media_type = embed`. `caption` is
 * translatable.
 */
class BrandProjectMedia extends Model implements HasMedia
{
    /** @use HasFactory<BrandProjectMediaFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia;

    protected $table = 'brand_project_media';

    /**
     * @var list<string>
     */
    protected $fillable = ['brand_project_id', 'media_type', 'embed_url', 'caption', 'position'];

    /**
     * @var array<int, string>
     */
    public array $translatable = ['caption'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('media')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(600)->height(600)->nonQueued();
    }

    /**
     * Uploaded media URL, or the external embed URL for embeds.
     */
    public function getMediaUrlAttribute(): ?string
    {
        if ($this->media_type === 'embed') {
            return $this->embed_url;
        }

        $this->loadMissing('media');

        return $this->getFirstMediaUrl('media') ?: null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        $this->loadMissing('media');

        return $this->getFirstMediaUrl('media', 'thumb') ?: null;
    }

    /** @return BelongsTo<BrandProject, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(BrandProject::class, 'brand_project_id');
    }
}
