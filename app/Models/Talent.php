<?php

namespace App\Models;

use App\Models\Concerns\HasFeedIdentity;
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
 * identity + singular settings, its skill(s) via the talent_type pivot, its
 * reorderable profile_blocks, and all talent content tables. The avatar is an
 * uploaded asset served from the media library (accessor below). headline/bio
 * are translatable.
 */
class Talent extends Authenticatable implements \App\Feed\Actor, HasMedia
{
    /** @use HasFactory<TalentFactory> */
    use HasApiTokens, HasFactory, HasFeedIdentity, HasStates, HasTranslations, InteractsWithMedia, Notifiable, SoftDeletes;

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
        'slug', 'display_name', 'headline', 'bio', 'worldview',
        'status', 'base_city', 'base_country', 'booking_type', 'booking_value',
        'will_travel', 'is_available', 'availability_changed_at',
        'rate_unit', 'rate_amount', 'rate_currency',
        'is_verified', 'is_published', 'published_at', 'view_count', 'meta',
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
    public array $translatable = ['headline', 'bio', 'worldview'];

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
            'last_posted_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'will_travel' => 'boolean',
            'is_available' => 'boolean',
            'availability_changed_at' => 'datetime',
            'steps_completed_at' => 'datetime',
            'is_published' => 'boolean',
            'meta' => 'array',
            'view_count' => 'integer',
            'rate_amount' => 'decimal:2',
            'status' => TalentProfileState::class,
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
     * Single-file collection for the uploaded avatar. (The `hero`/cover image was
     * removed with the Instagram-style header — ADR-O.)
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    /**
     * A square thumbnail conversion for the avatar.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(400)->height(400)->nonQueued();
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
     * The skill(s) this talent works as; `is_primary` leads the headline.
     * (`talent_types` is the Skills catalog — schema-master §1.)
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
     * The specialities this talent claims (ADR-Y), across every skill they hold.
     * Always a subset of the specialities belonging to those skills —
     * SkillsService is what enforces that; nothing else may attach here.
     *
     * @return BelongsToMany<Speciality, $this>
     */
    public function specialities(): BelongsToMany
    {
        return $this->belongsToMany(Speciality::class, 'speciality_talent')
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    /**
     * Social handles — "anywhere else the work lives" (ADR-Z, About).
     *
     * @return HasMany<TalentSocialHandle, $this>
     */
    public function socialHandles(): HasMany
    {
        return $this->hasMany(TalentSocialHandle::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Peer vouches written ABOUT this talent (ADR-Z). The sibling of reviews:
     * a review is brand → talent after a booking; a recognition is talent →
     * talent, invited, and therefore works from day one.
     *
     * @return HasMany<Recognition, $this>
     */
    public function recognitions(): HasMany
    {
        return $this->hasMany(Recognition::class)->latest('recognized_at');
    }

    /**
     * Peer vouches this talent has written about OTHERS.
     *
     * @return HasMany<Recognition, $this>
     */
    public function recognitionsWritten(): HasMany
    {
        return $this->hasMany(Recognition::class, 'author_talent_id');
    }

    /**
     * Credits naming this talent (ADR-AA) — every state, including the pending
     * confirmations they still owe an answer to.
     *
     * @return HasMany<ProjectCredit, $this>
     */
    public function projectCredits(): HasMany
    {
        return $this->hasMany(ProjectCredit::class);
    }

    /**
     * Projects this talent was CREDITED on and accepted — other people's
     * projects that now belong on their portfolio (ADR-AA).
     *
     * The pivot carries `talent_type_id`, which is the skill they were credited
     * in and therefore the tab the project lands in on their profile. It is
     * deliberately the CREDIT's skill, not the project's: a project can be a
     * photography job that you were credited on as a stylist.
     *
     * @return BelongsToMany<Project, $this>
     */
    public function creditedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_credits')
            ->wherePivot('status', 'accepted')
            ->withPivot('talent_type_id', 'role', 'position')
            ->orderByPivot('position');
    }

    /**
     * THE portfolio view of projects for one skill tab (ADR-AA): the projects
     * this talent OWNS in that scope, plus the ones they were CREDITED on and
     * accepted.
     *
     * A credited project lands in the tab of the CREDIT's skill, not the
     * project's own — you can be credited as a stylist on someone else's
     * photography job, and it belongs under Styling on your page.
     *
     * Reads loaded relations only, so a profile render stays N+1-free; callers
     * must eager-load `projects.talentTypes` and `creditedProjects`.
     *
     * @return \Illuminate\Support\Collection<int, Project>
     */
    public function portfolioProjects(?int $talentTypeId): \Illuminate\Support\Collection
    {
        $owned = $this->projects->filter(
            fn (Project $project) => $project->belongsToScope($talentTypeId)
        );

        if (! $this->relationLoaded('creditedProjects')) {
            return $owned->values();
        }

        $heldSkillIds = $this->talentTypes->pluck('id')->map('intval');

        $credited = $this->creditedProjects->filter(function (Project $project) use ($talentTypeId, $heldSkillIds) {
            $creditSkill = (int) $project->pivot->talent_type_id;

            // Normally a credited project lands in the tab of the skill it was
            // credited in. But the project owner picks that skill, and it need
            // not be one this talent lists — in which case there is no tab for
            // it, and the work would silently vanish after they confirmed it.
            // Those fall back to the universal region so a confirmation always
            // puts the work somewhere visible.
            return $heldSkillIds->contains($creditSkill)
                ? $creditSkill === (int) $talentTypeId
                : $talentTypeId === null;
        })
            // A project you own AND are credited on must appear once.
            ->reject(fn (Project $project) => $owned->contains('id', $project->id));

        return $owned->concat($credited)->values();
    }

    /**
     * "Willing to explore" — directions they want to move toward (ADR-Z).
     *
     * @return HasMany<TalentInterest, $this>
     */
    public function interests(): HasMany
    {
        return $this->hasMany(TalentInterest::class)->orderBy('position')->orderBy('id');
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
     * Daily profile-view buckets (dashboard views KPI + chart).
     *
     * @return HasMany<TalentProfileView, $this>
     */
    public function profileViews(): HasMany
    {
        return $this->hasMany(TalentProfileView::class);
    }

    /**
     * Contracts this talent is party to.
     *
     * @return HasMany<Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Pre-auth booking enquiries for this talent.
     *
     * @return HasMany<ContractEnquiry, $this>
     */
    public function contractEnquiries(): HasMany
    {
        return $this->hasMany(ContractEnquiry::class);
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
}
