<?php

namespace App\Models;

use App\States\Brand\BrandState;
use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\ModelStates\HasStates;
use Spatie\Translatable\HasTranslations;

/**
 * Brand login entity + public identity (schema-master §4). The `brand` guard.
 * Onboarding fills the profile progressively (fields nullable); `is_complete`
 * gates transacting, `is_published` gates talent-visibility, `is_verified` is a
 * one-way admin trust upgrade. Logo + cover are medialibrary collections (ADR-5),
 * not columns; `description` is translatable.
 */
class Brand extends Authenticatable implements HasMedia
{
    /** @use HasFactory<BrandFactory> */
    use HasApiTokens, HasFactory, HasStates, HasTranslations, InteractsWithMedia, Notifiable, SoftDeletes;

    protected $table = 'brands';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'email', 'password', 'phone', 'name', 'slug', 'description', 'industry', 'brand_stage',
        'base_city', 'base_country', 'geographic_reach', 'founded_year', 'company_size', 'website',
        'is_complete', 'is_active', 'is_verified', 'is_published', 'status', 'meta',
    ];

    /**
     * @var array<int, string>
     */
    public array $translatable = ['description'];

    /**
     * @var list<string>
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_complete' => 'boolean',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'is_published' => 'boolean',
            'status' => BrandState::class,
            'founded_year' => 'integer',
            'view_count' => 'integer',
            'meta' => 'array',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('cover')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(600)->height(400)->nonQueued();
    }

    public function getLogoUrlAttribute(): ?string
    {
        $this->loadMissing('media');

        return $this->getFirstMediaUrl('logo') ?: null;
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        $this->loadMissing('media');

        return $this->getFirstMediaUrl('cover') ?: null;
    }

    /** @return HasOne<BrandAesthetic, $this> */
    public function aesthetic(): HasOne
    {
        return $this->hasOne(BrandAesthetic::class);
    }

    /** @return HasOne<BrandCreativeNeed, $this> */
    public function creativeNeed(): HasOne
    {
        return $this->hasOne(BrandCreativeNeed::class);
    }

    /** @return HasOne<BrandCredibility, $this> */
    public function credibility(): HasOne
    {
        return $this->hasOne(BrandCredibility::class);
    }

    /** @return HasMany<BrandImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(BrandImage::class)->orderBy('position');
    }

    /** @return HasMany<BrandReview, $this> */
    public function brandReviews(): HasMany
    {
        return $this->hasMany(BrandReview::class);
    }

    /** @return HasMany<BrandSocialHandle, $this> */
    public function socialHandles(): HasMany
    {
        return $this->hasMany(BrandSocialHandle::class)->orderBy('position');
    }

    /** @return HasMany<BrandSignal, $this> */
    public function signals(): HasMany
    {
        return $this->hasMany(BrandSignal::class);
    }

    /** @return HasMany<Campaign, $this> */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /** @return HasMany<Deal, $this> */
    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }
}
