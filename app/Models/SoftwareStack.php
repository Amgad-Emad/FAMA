<?php

namespace App\Models;

use Database\Factories\SoftwareStackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * SoftwareStack — a tool a creative uses, with proficiency (schema-master §2).
 * `software_name` stays plain (queryable — "designers who know Figma"); the icon
 * is an uploaded asset.
 */
class SoftwareStack extends Model implements HasMedia
{
    /** @use HasFactory<SoftwareStackFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * Explicit table (singular by design).
     *
     * @var string
     */
    protected $table = 'software_stack';

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = ['talent_id', 'software_name', 'proficiency', 'position'];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    /**
     * Single uploaded software icon.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('icon')->singleFile();
    }

    /**
     * Small icon thumbnail conversion.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(96)->height(96)->nonQueued();
    }

    /**
     * Software icon URL from the media library (null if none).
     */
    public function getIconUrlAttribute(): ?string
    {
        $this->loadMissing('media');

        return $this->getFirstMediaUrl('icon') ?: null;
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
