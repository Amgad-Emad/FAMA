<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * FeedSeen — a viewer × post exposure mark (schema-master §7). Damps repeats
 * across feed sessions and feeds discovery freshness; written lazily, pruned
 * after 60 days by feed:prune. No Eloquent timestamps — `seen_at` is the fact.
 */
class FeedSeen extends Model
{
    use HasFactory;

    /**
     * The table name doesn't pluralize.
     *
     * @var string
     */
    protected $table = 'feed_seen';

    public $timestamps = false;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'viewer_type', 'viewer_id', 'post_id', 'seen_at',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'seen_at' => 'datetime',
        ];
    }

    /**
     * The viewing actor (talent or brand).
     *
     * @return MorphTo<Model, $this>
     */
    public function viewer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The seen post.
     *
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
