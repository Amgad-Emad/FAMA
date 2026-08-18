<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * Project — a long-form creative project write-up (schema-master §2). Cover is
 * an uploaded asset; `url` is the external project link. title/role/summary/body
 * are translatable; `results` holds flexible metrics.
 */
class Project extends Model implements HasMedia
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'talent_id', 'talent_type_id', 'title', 'client_name', 'role', 'summary', 'body',
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
