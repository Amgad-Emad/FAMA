<?php

namespace App\Models;

use Database\Factories\SpecialityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

/**
 * Speciality — the second level of the controlled vocabulary (ADR-Y).
 *
 * Every speciality answers one question and only one: what kind of SUBJECT.
 * Output format (campaign, editorial, lookbook, e-commerce) is a property of the
 * project, not of the person, and deliberately stays out of this tree.
 *
 * A speciality belongs to exactly one skill. The same word may appear under
 * several skills — "Fashion" is a photography speciality, a modelling speciality
 * and a styling speciality — which is why `slug` is unique per skill and not
 * globally. That overlap is the feature: it is what lets one search assemble a
 * whole crew.
 */
class Speciality extends Model
{
    /** @use HasFactory<SpecialityFactory> */
    use HasFactory, HasTranslations;

    /**
     * Laravel would guess `specialitys`.
     */
    protected $table = 'specialities';

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'talent_type_id', 'name', 'slug', 'description', 'is_regional', 'is_active', 'position',
    ];

    /**
     * Translatable attributes.
     *
     * @var array<int, string>
     */
    public array $translatable = ['name', 'description'];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_regional' => 'boolean',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * The skill this speciality nests under.
     *
     * @return BelongsTo<TalentType, $this>
     */
    public function talentType(): BelongsTo
    {
        return $this->belongsTo(TalentType::class);
    }

    /**
     * Talents claiming this speciality.
     *
     * @return BelongsToMany<Talent, $this>
     */
    public function talents(): BelongsToMany
    {
        return $this->belongsToMany(Talent::class, 'speciality_talent')
            ->withPivot('position')
            ->withTimestamps();
    }

    /**
     * Projects that drew on this speciality.
     *
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_speciality')
            ->withPivot('position')
            ->withTimestamps();
    }

    /**
     * Only the specialities a talent may still choose. Retiring an entry hides it
     * from every picker while leaving existing claims rendering — the same
     * grandfathering rule the block catalog uses (ADR-T).
     *
     * @param  Builder<Speciality>  $query
     */
    public function scopeSelectable(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
