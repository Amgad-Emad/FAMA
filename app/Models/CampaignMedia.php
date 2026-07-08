<?php

namespace App\Models;

use Database\Factories\CampaignMediaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * Campaign media (schema-master §5) — one gallery item. Uploaded image/video go
 * through the medialibrary `media` collection (`media_url`/`thumbnail_url`
 * accessors); `embed_url` is external for `media_type = embed`. `caption` is
 * translatable.
 */
class CampaignMedia extends Model implements HasMedia
{
    /** @use HasFactory<CampaignMediaFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia;

    protected $table = 'campaign_media';

    /**
     * @var list<string>
     */
    protected $fillable = ['campaign_id', 'media_type', 'embed_url', 'caption', 'position'];

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

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
