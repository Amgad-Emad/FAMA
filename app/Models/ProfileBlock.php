<?php

namespace App\Models;

use Database\Factories\ProfileBlockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * ProfileBlock — the malleable layout/arrangement layer (schema-master §1). One
 * row per block a talent shows, carrying position/visibility/layout and either
 * inline `content` (JSON) or a link to a rich content table. `title` is
 * translatable. The block type is always eager-loaded (rendering needs it).
 */
class ProfileBlock extends Model
{
    /** @use HasFactory<ProfileBlockFactory> */
    use HasFactory, HasTranslations;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'talent_id', 'block_type_id', 'title', 'position', 'is_visible',
        'layout', 'settings', 'content',
    ];

    /**
     * Translatable attributes.
     *
     * @var array<int, string>
     */
    public array $translatable = ['title'];

    /**
     * Always resolve the catalog entry when loading a block.
     *
     * @var array<int, string>
     */
    protected $with = ['blockType'];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_visible' => 'boolean',
            'settings' => 'array',
            'content' => 'array',
        ];
    }

    /**
     * The talent this block belongs to.
     *
     * @return BelongsTo<Talent, $this>
     */
    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    /**
     * The catalog entry that defines how this block renders.
     *
     * @return BelongsTo<BlockType, $this>
     */
    public function blockType(): BelongsTo
    {
        return $this->belongsTo(BlockType::class);
    }

    /**
     * Gallery items attached to this block (when it is a gallery).
     *
     * @return HasMany<PortfolioItem, $this>
     */
    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class, 'block_id')->orderBy('position');
    }
}
