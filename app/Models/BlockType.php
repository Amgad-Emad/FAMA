<?php

namespace App\Models;

use Database\Factories\BlockTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * BlockType — the admin-governed block catalog (schema-master §1). `availability`
 * decides whether every talent can add the block (universal) or only certain
 * categories (by_category → block_type_category) or skills (by_type →
 * block_type_talent_type). name/description are translatable.
 */
class BlockType extends Model
{
    /** @use HasFactory<BlockTypeFactory> */
    use HasFactory, HasTranslations;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'key', 'name', 'description', 'icon', 'availability', 'content_source',
        'default_layout', 'is_active', 'is_repeatable', 'position', 'settings_schema',
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
            'is_active' => 'boolean',
            'is_repeatable' => 'boolean',
            'position' => 'integer',
            'settings_schema' => 'array',
        ];
    }

    /**
     * Categories this block is gated to (when availability = by_category).
     *
     * @return HasMany<BlockTypeCategory, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(BlockTypeCategory::class);
    }

    /**
     * Skills this block is gated to (when availability = by_type).
     *
     * @return BelongsToMany<TalentType, $this>
     */
    public function talentTypes(): BelongsToMany
    {
        return $this->belongsToMany(TalentType::class, 'block_type_talent_type');
    }

    /**
     * Profile blocks rendered from this catalog entry.
     *
     * @return HasMany<ProfileBlock, $this>
     */
    public function profileBlocks(): HasMany
    {
        return $this->hasMany(ProfileBlock::class);
    }
}
