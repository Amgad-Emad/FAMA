<?php

namespace App\Models;

use Database\Factories\TalentTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

/**
 * TalentType — the six-profession lookup (schema-master §1). Its `default_blocks`
 * (ordered block_type keys) drives which blocks a new talent of this type gets
 * seeded. name/description are translatable.
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
     * Talents who work as this profession.
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
     * Block types gated to this specific profession (availability = by_type).
     *
     * @return BelongsToMany<BlockType, $this>
     */
    public function blockTypes(): BelongsToMany
    {
        return $this->belongsToMany(BlockType::class, 'block_type_talent_type');
    }
}
