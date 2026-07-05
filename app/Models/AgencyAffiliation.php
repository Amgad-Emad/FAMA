<?php

namespace App\Models;

use Database\Factories\AgencyAffiliationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * AgencyAffiliation — representation info (schema-master §2). `agency_url` is the
 * external agency link; the logo is an uploaded asset. `is_current` flags active
 * representation.
 */
class AgencyAffiliation extends Model implements HasMedia
{
    /** @use HasFactory<AgencyAffiliationFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'talent_id', 'agency_name', 'agency_url', 'representation_type', 'region', 'is_current',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_current' => 'boolean'];
    }

    /**
     * Single uploaded agency logo.
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
     * Agency logo URL from the media library (null if none).
     */
    public function getAgencyLogoUrlAttribute(): ?string
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
