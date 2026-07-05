<?php

namespace App\Models;

use Database\Factories\BlockTypeCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BlockTypeCategory — which category a `by_category` block applies to
 * (schema-master §1). A thin pivot-style row; no timestamps.
 */
class BlockTypeCategory extends Model
{
    /** @use HasFactory<BlockTypeCategoryFactory> */
    use HasFactory;

    /**
     * Explicit singular pivot table name.
     *
     * @var string
     */
    protected $table = 'block_type_category';

    /**
     * This table has no created_at/updated_at.
     */
    public $timestamps = false;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = ['block_type_id', 'category'];

    /**
     * The catalog entry this gate belongs to.
     *
     * @return BelongsTo<BlockType, $this>
     */
    public function blockType(): BelongsTo
    {
        return $this->belongsTo(BlockType::class);
    }
}
