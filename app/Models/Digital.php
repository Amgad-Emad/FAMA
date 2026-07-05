<?php

namespace App\Models;

use Database\Factories\DigitalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Digital — a model polaroid/digital (schema-master §2). The shot is an uploaded
 * asset (media library); `media_url`/`thumbnail_url` are accessors, not columns.
 */
class Digital extends Model implements HasMedia
{
    /** @use HasFactory<DigitalFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = ['talent_id', 'shot_type', 'captured_at', 'position'];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'captured_at' => 'date',
            'position' => 'integer',
        ];
    }

    /**
     * Single uploaded digital, with a thumbnail conversion.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('digital')->singleFile();
    }

    /**
     * Card-sized thumbnail.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(400)->height(400)->nonQueued();
    }

    /**
     * Full digital URL from the media library (null if none).
     */
    public function getMediaUrlAttribute(): ?string
    {
        $this->loadMissing('media');

        return $this->getFirstMediaUrl('digital') ?: null;
    }

    /**
     * Digital thumbnail URL (falls back to the full image).
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        $this->loadMissing('media');

        return ($this->getFirstMediaUrl('digital', 'thumb') ?: $this->getFirstMediaUrl('digital')) ?: null;
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
