<?php

namespace App\Models;

use App\States\Availability\AvailabilityState;
use App\States\TalentProfile\TalentProfileState;
use Database\Factories\TalentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\ModelStates\HasStates;
use Spatie\Translatable\HasTranslations;

/**
 * Talent — the living creative passport (schema-master §1).
 *
 * The `talent` guard's Authenticatable, plus the rich profile: one-per-profile
 * identity + singular settings, its profession(s) via the talent_type pivot, its
 * reorderable profile_blocks, and all talent content tables. Hero/avatar are
 * uploaded assets served from the media library (accessors below). headline/bio
 * are translatable.
 */
class Talent extends Authenticatable implements HasMedia
{
    /** @use HasFactory<TalentFactory> */
    use HasApiTokens, HasFactory, HasStates, HasTranslations, InteractsWithMedia, Notifiable, SoftDeletes;

    /**
     * Explicit table — the inflector leaves "talent" unpluralized.
     *
     * @var string
     */
    protected $table = 'talents';

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email', 'password', 'email_verified_at', 'phone', 'last_login_at', 'is_active',
        'slug', 'display_name', 'headline', 'bio',
        'availability_status', 'status', 'base_city', 'base_country', 'rate_tier',
        'willing_to_travel', 'travel_regions', 'booking_type', 'booking_value',
        'is_published', 'published_at', 'view_count', 'meta',
    ];

    /**
     * Attributes hidden from serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Translatable (per-locale JSON) attributes.
     *
     * @var array<int, string>
     */
    public array $translatable = ['headline', 'bio'];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'published_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'willing_to_travel' => 'boolean',
            'is_published' => 'boolean',
            'travel_regions' => 'array',
            'meta' => 'array',
            'view_count' => 'integer',
            'status' => TalentProfileState::class,
            'availability_status' => AvailabilityState::class,
        ];
    }

    /**
     * Ensure every talent has a unique public slug (generated if not supplied).
     */
    protected static function booted(): void
    {
        static::creating(function (Talent $talent): void {
            if (blank($talent->slug)) {
                $base = Str::slug($talent->display_name ?: 'talent');
                $talent->slug = $base.'-'.Str::lower(Str::random(6));
            }
        });
    }

    // ----- Media -------------------------------------------------------------

    /**
     * Single-file collections for the uploaded identity images.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero')->singleFile();
        $this->addMediaCollection('avatar')->singleFile();
    }

    /**
     * A square thumbnail conversion for both identity images.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(400)->height(400)->nonQueued();
    }

    /**
     * Public hero image URL, resolved from the media library (null if none).
     */
    public function getHeroImageUrlAttribute(): ?string
    {
        $this->loadMissing('media');

        return $this->getFirstMediaUrl('hero') ?: null;
    }

    /**
     * Public avatar URL, resolved from the media library (null if none).
     */
    public function getAvatarUrlAttribute(): ?string
    {
        $this->loadMissing('media');

        return $this->getFirstMediaUrl('avatar') ?: null;
    }

    // ----- Relationships -----------------------------------------------------

    /**
     * The profession(s) this talent works as; `is_primary` leads the headline.
     *
     * @return BelongsToMany<TalentType, $this>
     */
    public function talentTypes(): BelongsToMany
    {
        return $this->belongsToMany(TalentType::class, 'talent_talent_type')
            ->withPivot('is_primary', 'position')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    /**
     * The reorderable layout layer (profile_blocks) in display order.
     *
     * @return HasMany<ProfileBlock, $this>
     */
    public function profileBlocks(): HasMany
    {
        return $this->hasMany(ProfileBlock::class)->orderBy('position');
    }

    /**
     * Gallery items.
     *
     * @return HasMany<PortfolioItem, $this>
     */
    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class)->orderBy('position');
    }

    /**
     * Past brand collaborations.
     *
     * @return HasMany<BrandCollab, $this>
     */
    public function brandCollabs(): HasMany
    {
        return $this->hasMany(BrandCollab::class)->orderBy('position');
    }

    /**
     * Client/peer reviews.
     *
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Rate-card services.
     *
     * @return HasMany<Service, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class)->orderBy('position');
    }

    /**
     * Deals this talent is party to.
     *
     * @return HasMany<Deal, $this>
     */
    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    /**
     * Pre-auth booking enquiries for this talent.
     *
     * @return HasMany<DealEnquiry, $this>
     */
    public function dealEnquiries(): HasMany
    {
        return $this->hasMany(DealEnquiry::class);
    }

    /**
     * The 1:1 model comp card.
     *
     * @return HasOne<CompCard, $this>
     */
    public function compCard(): HasOne
    {
        return $this->hasOne(CompCard::class);
    }

    /**
     * Model looks.
     *
     * @return HasMany<LookType, $this>
     */
    public function lookTypes(): HasMany
    {
        return $this->hasMany(LookType::class)->orderBy('position');
    }

    /**
     * Model digitals/polaroids.
     *
     * @return HasMany<Digital, $this>
     */
    public function digitals(): HasMany
    {
        return $this->hasMany(Digital::class)->orderBy('position');
    }

    /**
     * Crew/creative showreels.
     *
     * @return HasMany<Showreel, $this>
     */
    public function showreels(): HasMany
    {
        return $this->hasMany(Showreel::class)->orderBy('position');
    }

    /**
     * Crew equipment/kit.
     *
     * @return HasMany<Equipment, $this>
     */
    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class)->orderBy('position');
    }

    /**
     * Creative projects.
     *
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class)->orderBy('position');
    }

    /**
     * Creative software stack.
     *
     * @return HasMany<SoftwareStack, $this>
     */
    public function softwareStack(): HasMany
    {
        return $this->hasMany(SoftwareStack::class)->orderBy('position');
    }

    /**
     * Agency representation.
     *
     * @return HasMany<AgencyAffiliation, $this>
     */
    public function agencyAffiliations(): HasMany
    {
        return $this->hasMany(AgencyAffiliation::class);
    }

    /**
     * Press features.
     *
     * @return HasMany<PressFeature, $this>
     */
    public function pressFeatures(): HasMany
    {
        return $this->hasMany(PressFeature::class)->orderBy('position');
    }
}
