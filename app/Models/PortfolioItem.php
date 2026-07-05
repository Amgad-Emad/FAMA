<?php

namespace App\Models;

use Database\Factories\PortfolioItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * PortfolioItem — a gallery entry (schema-master §2). Image/video uploads live in
 * the media library; `embed` items keep the external URL. `caption` is
 * translatable. `media_url`/`thumbnail_url` are accessors, not columns.
 */
class PortfolioItem extends Model implements HasMedia
{
    /** @use HasFactory<PortfolioItemFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'talent_id', 'block_id', 'media_type', 'embed_url', 'caption', 'credits', 'tags', 'position',
    ];

    /**
     * Translatable attributes.
     *
     * @var array<int, string>
     */
    public array $translatable = ['caption'];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credits' => 'array',
            'tags' => 'array',
            'position' => 'integer',
        ];
    }

    /**
     * Single uploaded file per item, with a thumbnail conversion.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery')->singleFile();
    }

    /**
     * Card-sized thumbnail.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(400)->height(400)->nonQueued();
    }

    /**
     * Full media URL — the external embed for `embed`, else the uploaded file.
     */
    public function getMediaUrlAttribute(): ?string
    {
        if ($this->media_type === 'embed') {
            return $this->embed_url;
        }

        $this->loadMissing('media');

        return $this->getFirstMediaUrl('gallery') ?: null;
    }

    /**
     * Thumbnail URL — the conversion when uploaded, else the media/embed URL.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->media_type === 'embed') {
            return $this->embed_url;
        }

        $this->loadMissing('media');

        return ($this->getFirstMediaUrl('gallery', 'thumb') ?: $this->getFirstMediaUrl('gallery')) ?: null;
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

    /**
     * The gallery block this item belongs to (nullable).
     *
     * @return BelongsTo<ProfileBlock, $this>
     */
    public function block(): BelongsTo
    {
        return $this->belongsTo(ProfileBlock::class, 'block_id');
    }
}
