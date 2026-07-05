<?php

namespace App\Models;

use Database\Factories\CaseStudyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * CaseStudy — a long-form creative project write-up (schema-master §2). Cover is
 * an uploaded asset; `url` is the external project link. title/role/summary/body
 * are translatable; `results` holds flexible metrics.
 */
class CaseStudy extends Model implements HasMedia
{
    /** @use HasFactory<CaseStudyFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'talent_id', 'title', 'client_name', 'role', 'summary', 'body',
        'results', 'year', 'url', 'position',
    ];

    /**
     * Translatable attributes.
     *
     * @var array<int, string>
     */
    public array $translatable = ['title', 'role', 'summary', 'body'];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'results' => 'array',
            'year' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * Single uploaded cover image.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
    }

    /**
     * Wide cover thumbnail conversion.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(800)->height(450)->nonQueued();
    }

    /**
     * Cover image URL from the media library (null if none).
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        $this->loadMissing('media');

        return $this->getFirstMediaUrl('cover') ?: null;
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
