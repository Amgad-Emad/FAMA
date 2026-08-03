<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * LinkedUrl — joins a body (`linkable` = post/comment) to its LinkPreview,
 * positioned in order of appearance (schema-master §7). Rebuilt wholesale on
 * body edit, like mentions.
 */
class LinkedUrl extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'linkable_type', 'linkable_id', 'link_preview_id', 'position',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * The body the URL appears in (post or comment).
     *
     * @return MorphTo<Model, $this>
     */
    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The deduplicated preview row.
     *
     * @return BelongsTo<LinkPreview, $this>
     */
    public function preview(): BelongsTo
    {
        return $this->belongsTo(LinkPreview::class, 'link_preview_id');
    }
}
