<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Mention — one @mention inside a body (schema-master §7, ADR-F3):
 * `mentionable` (post/comment) → `mentioned` (talent/brand), located by
 * multibyte-correct `start_offset` + `length` into the RAW body. The renderer
 * resolves the current Username at read time; edits delete and re-parse.
 */
class Mention extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'mentionable_type', 'mentionable_id', 'mentioned_type', 'mentioned_id',
        'start_offset', 'length',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_offset' => 'integer',
            'length' => 'integer',
        ];
    }

    /**
     * The body the mention lives in (post or comment).
     *
     * @return MorphTo<Model, $this>
     */
    public function mentionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The mentioned actor (talent or brand).
     *
     * @return MorphTo<Model, $this>
     */
    public function mentioned(): MorphTo
    {
        return $this->morphTo();
    }
}
