<?php

namespace App\Models;

use Database\Factories\TalentTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * TalentType — the Skills catalog (schema-master §1): the six seeded skills a
 * talent can work as. (The physical table stays `talent_types`; "Skills" is the
 * product term — ADR-N.) Its `default_blocks` (ordered block_type keys) drives
 * which blocks a new talent of this skill gets seeded. name/description are
 * translatable.
 */
class TalentType extends Model
{
    /** @use HasFactory<TalentTypeFactory> */
    use HasFactory, HasTranslations;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = ['name', 'slug', 'category', 'default_blocks', 'icon', 'description'];

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
            'default_blocks' => 'array',
        ];
    }

    /**
     * Talents who work with this skill.
     *
     * @return BelongsToMany<Talent, $this>
     */
    public function talents(): BelongsToMany
    {
        return $this->belongsToMany(Talent::class, 'talent_talent_type')
            ->withPivot('is_primary', 'position')
            ->withTimestamps();
    }

    /**
     * The specialities nesting under this skill (ADR-Y) — the controlled list a
     * talent picks from once they hold the skill. Ordered as the catalog orders
     * them, not alphabetically: the admin's order is editorial.
     *
     * @return HasMany<Speciality, $this>
     */
    public function specialities(): HasMany
    {
        return $this->hasMany(Speciality::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Block types gated to this specific skill (availability = by_type).
     *
     * @return BelongsToMany<BlockType, $this>
     */
    public function blockTypes(): BelongsToMany
    {
        return $this->belongsToMany(BlockType::class, 'block_type_talent_type');
    }

    /**
     * Skills in alphabetical order of their English name — how the prototype
     * lists all 41 in every picker and filter (ADR-AF).
     */
    public function scopeAlphabetical(Builder $query): Builder
    {
        return $query->orderBy('name->en');
    }
}
