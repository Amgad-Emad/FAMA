<?php

namespace App\Models;

use Database\Factories\ShowreelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * Showreel — a crew/creative video reel (schema-master §2). `video_url` is the
 * EXTERNAL embed (YouTube/Vimeo/self-hosted); the poster thumbnail is an uploaded
 * asset. `title` is translatable.
 */
class Showreel extends Model implements HasMedia
{
    /** @use HasFactory<ShowreelFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = ['talent_id', 'title', 'video_url', 'platform', 'duration_seconds', 'position'];

    /**
     * Translatable attributes.
     *
     * @var array<int, string>
     */
    public array $translatable = ['title'];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * Single uploaded poster thumbnail.
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
     * Poster thumbnail URL from the media library (null if none).
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
