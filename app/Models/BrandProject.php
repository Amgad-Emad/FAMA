<?php

namespace App\Models;

use App\States\BrandProject\BrandProjectState;
use Database\Factories\BrandProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\ModelStates\HasStates;
use Spatie\Translatable\HasTranslations;

/**
 * Project (schema-master §5) — a brand's public-facing project that can group
 * many contracts. `status` (draft/open/in_progress/completed/cancelled) becomes a
 * state machine in Phase 2B; `is_public` toggles listed ⇄ private independently.
 * Cover is a medialibrary collection; `description` is translatable.
 */
class BrandProject extends Model implements HasMedia
{
    /** @use HasFactory<BrandProjectFactory> */
    use HasFactory, HasStates, HasTranslations, InteractsWithMedia, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'brand_id', 'title', 'slug', 'type', 'description', 'status', 'budget_min', 'budget_max',
        'currency', 'location_city', 'location_country', 'start_date', 'end_date', 'is_public',
        'talent_type_id', 'budget_is_public',
    ];

    /**
     * @var array<int, string>
     */
    public array $translatable = ['description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BrandProjectState::class,
            'budget_min' => 'decimal:2',
            'budget_max' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_public' => 'boolean',
            'budget_is_public' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(1200)->height(675)->nonQueued();
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        $this->loadMissing('media');

        return $this->getFirstMediaUrl('cover') ?: null;
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * The single role/discipline this project seeks (one role, one position).
     *
     * @return BelongsTo<TalentType, $this>
     */
    public function talentType(): BelongsTo
    {
        return $this->belongsTo(TalentType::class);
    }

    /**
     * The project gallery (uploaded media rows).
     *
     * @return HasMany<BrandProjectMedia, $this>
     */
    public function gallery(): HasMany
    {
        return $this->hasMany(BrandProjectMedia::class)->orderBy('position');
    }

    /**
     * Contracts running under this project (contracts.brand_project_id).
     *
     * @return HasMany<Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Completed, public projects — the showcases on the brand profile.
     *
     * @param  Builder<BrandProject>  $query
     */
    public function scopeShowcase(Builder $query): void
    {
        $query->where('status', 'completed')->where('is_public', true);
    }
}
