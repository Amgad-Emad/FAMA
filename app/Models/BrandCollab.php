<?php

namespace App\Models;

use Database\Factories\BrandCollabFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * BrandCollab — past brand work (schema-master §2). The logo is an uploaded
 * asset (media library); `url` is the external project link. `project_title` is
 * translatable.
 */
class BrandCollab extends Model implements HasMedia
{
    /** @use HasFactory<BrandCollabFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = ['talent_id', 'brand_name', 'project_title', 'year', 'url', 'position'];

    /**
     * Translatable attributes.
     *
     * @var array<int, string>
     */
    public array $translatable = ['project_title'];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * Single uploaded brand logo.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    /**
     * Thumbnail conversion for the logo.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(200)->height(200)->nonQueued();
    }

    /**
     * Brand logo URL from the media library (null if none).
     */
    public function getBrandLogoUrlAttribute(): ?string
    {
        $this->loadMissing('media');

        return $this->getFirstMediaUrl('logo') ?: null;
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
