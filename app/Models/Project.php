<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * Project — a long-form creative project write-up (schema-master §2). Cover is
 * an uploaded asset; `url` is the external project link. title/role/summary/body
 * are translatable; `results` holds up to four metrics as an ordered list of
 * {label, value, compare} (resultRows() also reads the legacy label→value map).
 *
 * OWNERSHIP (co-ownership, 2026-10-12): the owners of a project are its creator
 * (`talent_id`, the "home owner" whose skills the tags must belong to — ADR-X)
 * plus every on-platform credit in state `accepted`. Any owner may edit or
 * leave; only a sole owner may delete. THIS model is the one home for that
 * fact (isOwnedBy / ownerIds / hasCoOwners / coOwnerCredits) — the policy, the
 * services, the controllers and the views all read it from here.
 */
class Project extends Model implements HasMedia
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia;

    /**
     * Mass-assignable attributes.
     *
     * `year` is deliberately ABSENT: since ADR-AA it is a derived projection of
     * `occurred_on`, written only by that field's mutator. Leaving it fillable
     * let a caller set the two independently and silently disagree — which is
     * exactly what happened to the demo seeder. With `preventSilentlyDiscarding
     * Attributes` on, an attempt to mass-assign it now throws instead.
     *
     * @var list<string>
     */
    protected $fillable = [
        'talent_id', 'talent_type_id', 'title', 'client_name', 'role', 'summary', 'body',
        'results', 'occurred_on', 'url', 'position',
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
            'occurred_on' => 'date',
            'position' => 'integer',
        ];
    }

    /**
     * Keep `year` as a derived projection of the date (ADR-AA).
     *
     * `occurred_on` is the field the editor writes, but `year` is still read by
     * the feed's project spreads, the API resources and existing rows. Deriving
     * it here — a MUTATOR, not a model event — means every write path stays in
     * sync, including seeders muted by `WithoutModelEvents` and factories, which
     * an observer would silently miss.
     */
    public function setOccurredOnAttribute(mixed $value): void
    {
        $date = $value === null || $value === '' ? null : Carbon::parse($value);

        $this->attributes['occurred_on'] = $date?->toDateString();

        if ($date !== null) {
            $this->attributes['year'] = (int) $date->year;
        }
    }

    /**
     * `cover` is the single lead image; `media` is THE WORK — images and video,
     * ordered by the person who logged the project (ADR-AA). The order is the
     * point: the first frame is doing the most work, so it is chosen rather than
     * defaulted, and medialibrary's `order_column` is what carries that choice.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
        $this->addMediaCollection('media');
    }

    /**
     * Wide cover thumbnail conversion.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(800)->height(450)->nonQueued();
    }

    /**
     * The ordered work, newest-first-by-choice rather than by upload time.
     *
     * Reads the loaded `media` relation so a listing stays N+1-free. WATCH OUT:
     * medialibrary accessors call getFirstMediaUrl(), which uses loadMissing()
     * internally and therefore slips past preventLazyLoading — any list touching
     * these must eager-load `media` itself.
     *
     * @return Collection<int, Media>
     */
    public function orderedMedia(): Collection
    {
        $this->loadMissing('media');

        return $this->media
            ->where('collection_name', 'media')
            ->sortBy('order_column')
            ->values();
    }

    /**
     * The lead image: the explicit cover, else the FIRST item of the ordered
     * media — which is the same "chosen, not defaulted" rule, one step down.
     */
    public function getLeadImageUrlAttribute(): ?string
    {
        return $this->cover_image_url ?? $this->orderedMedia()->first()?->getUrl();
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

    /**
     * The PRIMARY skill (home tab) this project belongs to; NULL once its skills
     * are removed (content is preserved — ADR-Q). Mirrors tag 0 of
     * `talentTypes()`, which is the full set (ADR-X).
     *
     * @return BelongsTo<TalentType, $this>
     */
    public function talentType(): BelongsTo
    {
        return $this->belongsTo(TalentType::class);
    }

    /**
     * Every skill this project is tagged with (ADR-X) — a project needs at least
     * one and renders in each tagged skill's tab. Ordered so tag 0 is the
     * primary skill mirrored onto `talent_type_id`.
     *
     * @return BelongsToMany<TalentType, $this>
     */
    public function talentTypes(): BelongsToMany
    {
        return $this->belongsToMany(TalentType::class, 'project_talent_type')
            ->withPivot('position')
            ->orderBy('project_talent_type.position');
    }

    /**
     * Everyone credited on this project (ADR-AA), in the owner's order.
     *
     * @return HasMany<ProjectCredit, $this>
     */
    public function credits(): HasMany
    {
        return $this->hasMany(ProjectCredit::class)->orderBy('position')->orderBy('id');
    }

    /**
     * The accepted on-platform credits — the co-owner rows — longest-standing
     * first (earliest confirmation, then id). Eager-load it (with `.talent`)
     * on lists so the ownership helpers below stay N+1-free.
     *
     * @return HasMany<ProjectCredit, $this>
     */
    public function ownerCredits(): HasMany
    {
        return $this->hasMany(ProjectCredit::class)
            ->where('status', 'accepted')
            ->whereNotNull('talent_id')
            ->orderByRaw('responded_at IS NULL, responded_at')
            ->orderBy('id');
    }

    /**
     * The co-owner credit rows, EXCLUDING any row naming the home owner (after
     * an ownership transfer the new home owner keeps their credit line — they
     * must not count as their own co-owner). Reads the loaded relation if any.
     *
     * @return Collection<int, ProjectCredit>
     */
    public function coOwnerCredits(): Collection
    {
        $credits = $this->relationLoaded('ownerCredits') ? $this->ownerCredits : $this->ownerCredits()->get();

        return $credits
            ->reject(fn (ProjectCredit $credit) => (int) $credit->talent_id === (int) $this->talent_id)
            ->unique('talent_id')
            ->values();
    }

    /**
     * Co-owner credits whose talent account still exists (not deleted) — the
     * only ones a project can be handed to. One query.
     */
    public function activeCoOwnerCredits(): Collection
    {
        $credits = $this->coOwnerCredits();
        $live = Talent::query()->whereKey($credits->pluck('talent_id'))->pluck('id')->map('intval')->all();

        return $credits->filter(fn (ProjectCredit $c) => in_array((int) $c->talent_id, $live, true))->values();
    }

    /**
     * Every owner's talent id: the home owner first, then co-owners by seniority.
     *
     * @return list<int>
     */
    public function ownerIds(): array
    {
        return collect([(int) $this->talent_id])
            ->concat($this->coOwnerCredits()->pluck('talent_id')->map('intval'))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * The owners as Talent models (home owner first). Callers eager-load
     * `talent` and `ownerCredits.talent`.
     *
     * @return Collection<int, Talent>
     */
    public function owners(): Collection
    {
        return collect([$this->talent])
            ->concat($this->coOwnerCredits()->map(fn (ProjectCredit $credit) => $credit->talent))
            ->filter()
            ->values();
    }

    /**
     * THE ownership test: is this talent the creator or an accepted co-owner?
     */
    public function isOwnedBy(?Talent $talent): bool
    {
        return $talent !== null && in_array((int) $talent->getKey(), $this->ownerIds(), true);
    }

    /**
     * Does anyone besides the home owner own this project? (Delete is refused
     * while this is true — the other owners would lose their work.)
     */
    public function hasCoOwners(): bool
    {
        return $this->coOwnerCredits()->isNotEmpty();
    }

    /**
     * Is this talent the project's ONLY owner (the one case delete is allowed)?
     */
    public function isSoleOwner(?Talent $talent): bool
    {
        return $this->isOwnedBy($talent) && ! $this->hasCoOwners();
    }

    /**
     * The results as an ordered list of {label, value, compare}, capped at four.
     * Reads both the current list shape and the legacy label→value map, so a row
     * the conversion migration has not reached still renders.
     *
     * @return list<array{label: string, value: string, compare: string}>
     */
    public function resultRows(): array
    {
        return self::normalizeResults($this->results ?? []);
    }

    /**
     * Normalise a results payload (list of rows, or the legacy map) to the
     * stored shape: non-blank label + value, trimmed, max four, compare ''.
     *
     * @param  array<int|string, mixed>  $results
     * @return list<array{label: string, value: string, compare: string}>
     */
    public static function normalizeResults(array $results): array
    {
        $rows = array_is_list($results)
            ? $results
            : collect($results)->map(fn ($value, $label) => ['label' => $label, 'value' => $value])->values()->all();

        return collect($rows)
            ->filter(fn ($row) => is_array($row))
            ->map(fn (array $row) => [
                'label' => trim((string) ($row['label'] ?? '')),
                'value' => trim(is_scalar($row['value'] ?? null) ? (string) $row['value'] : ''),
                'compare' => trim((string) ($row['compare'] ?? '')),
            ])
            ->filter(fn (array $row) => $row['label'] !== '' && $row['value'] !== '')
            ->take(4)
            ->values()
            ->all();
    }

    /**
     * The specialities this project drew on (ADR-Y) — always a subset of the
     * specialities belonging to the project's tagged skills. This is the link
     * that lets a brand searching for fashion photography in Riyadh find the
     * people who have actually shot it.
     *
     * @return BelongsToMany<Speciality, $this>
     */
    public function specialities(): BelongsToMany
    {
        return $this->belongsToMany(Speciality::class, 'project_speciality')
            ->withPivot('position')
            ->orderBy('project_speciality.position');
    }

    /**
     * The ids of the skills this project belongs to, home tab first.
     *
     * The home tab (`talent_type_id`) is deliberately UNIONED with the pivot
     * rather than read from it: a row written straight to the column — a factory,
     * a seeder muted by WithoutModelEvents, un-migrated legacy data — still
     * belongs in that tab. The pivot only ever ADDS tabs, so nothing can drop out
     * of the public profile because a tag row is missing.
     *
     * Reads the loaded relation when present so listings stay N+1-free.
     *
     * @return list<int>
     */
    public function skillIds(): array
    {
        $tags = $this->relationLoaded('talentTypes')
            ? $this->talentTypes->pluck('id')
            : $this->talentTypes()->pluck('talent_types.id');

        return collect([$this->talent_type_id, ...$tags])
            ->filter(fn ($id) => $id !== null)
            ->map('intval')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * True when this project belongs in the given skill scope — the tab test
     * (ADR-X). A NULL scope means the universal region, which holds the projects
     * that carry no skill at all.
     */
    public function belongsToScope(?int $talentTypeId): bool
    {
        return $talentTypeId === null
            ? $this->skillIds() === []
            : in_array((int) $talentTypeId, $this->skillIds(), true);
    }

    /**
     * Constrain to the projects that belong in one skill's tab — the query-side
     * twin of belongsToScope(), matching the home column OR a tag.
     *
     * @param  Builder<Project>  $query
     */
    public function scopeInSkillScope(Builder $query, int $talentTypeId): void
    {
        $query->where(fn (Builder $q) => $q
            ->where('talent_type_id', $talentTypeId)
            ->orWhereHas('talentTypes', fn ($tags) => $tags->whereKey($talentTypeId)));
    }

    /**
     * Constrain to the projects that carry no skill at all — what a removed skill
     * can leave behind (ADR-Q preserves the content).
     *
     * @param  Builder<Project>  $query
     */
    public function scopeWithoutSkill(Builder $query): void
    {
        $query->whereNull('talent_type_id')->whereDoesntHave('talentTypes');
    }
}
