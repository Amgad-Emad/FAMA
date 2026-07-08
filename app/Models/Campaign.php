<?php

namespace App\Models;

use App\States\Campaign\CampaignState;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\ModelStates\HasStates;
use Spatie\Translatable\HasTranslations;

/**
 * Campaign (schema-master §5) — a brand's public-facing project that can group
 * many deals. `status` (draft/open/in_progress/completed/cancelled) becomes a
 * state machine in Phase 2B; `is_public` toggles listed ⇄ private independently.
 * Cover is a medialibrary collection; `description` is translatable.
 */
class Campaign extends Model implements HasMedia
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory, HasStates, HasTranslations, InteractsWithMedia, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'brand_id', 'title', 'slug', 'type', 'description', 'status', 'budget_min', 'budget_max',
        'currency', 'location_city', 'location_country', 'start_date', 'end_date', 'is_public', 'positions_count',
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
            'status' => CampaignState::class,
            'budget_min' => 'decimal:2',
            'budget_max' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_public' => 'boolean',
            'positions_count' => 'integer',
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
     * Roles the campaign seeks (with per-role `quantity`).
     *
     * @return BelongsToMany<TalentType, $this>
     */
    public function talentTypes(): BelongsToMany
    {
        return $this->belongsToMany(TalentType::class, 'campaign_talent_types', 'campaign_id', 'talent_type_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * The campaign gallery (uploaded media rows).
     *
     * @return HasMany<CampaignMedia, $this>
     */
    public function gallery(): HasMany
    {
        return $this->hasMany(CampaignMedia::class)->orderBy('position');
    }

    /**
     * Deals running under this campaign (deals.campaign_id).
     *
     * @return HasMany<Deal, $this>
     */
    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    /**
     * Completed, public campaigns — the showcases on the brand profile.
     *
     * @param  Builder<Campaign>  $query
     */
    public function scopeShowcase(Builder $query): void
    {
        $query->where('status', 'completed')->where('is_public', true);
    }
}
