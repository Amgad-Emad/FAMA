<?php

namespace App\Models;

use App\Observers\CommentObserver;
use App\States\Comment\CommentState;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStates\HasStates;

/**
 * Comment — two-level thread on a post (schema-master §7, ADR-F7).
 *
 * `parent_id` NULL = top-level; a reply to a reply re-attaches to the same
 * parent with an auto-prefixed @mention, so depth never exceeds two. Author is
 * a talent/brand morph. A deleted comment with replies renders as a tombstone
 * (D12). Counters are denormalized (ADR-F6).
 */
#[ObservedBy(CommentObserver::class)]
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory, HasStates, SoftDeletes;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'post_id', 'parent_id', 'author_type', 'author_id', 'body', 'state', 'edited_at',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'state' => CommentState::class,
            'edited_at' => 'datetime',
            'likes_count' => 'integer',
            'replies_count' => 'integer',
        ];
    }

    /**
     * The post this comment (or reply) belongs to.
     *
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * The parent comment (replies only; NULL for top-level).
     *
     * @return BelongsTo<Comment, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Direct replies (always depth two — ADR-F7).
     *
     * @return HasMany<Comment, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * The writing actor (talent or brand).
     *
     * @return MorphTo<Model, $this>
     */
    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Likes on the comment.
     *
     * @return MorphMany<Like, $this>
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Mentions parsed from the body (ADR-F3).
     *
     * @return MorphMany<Mention, $this>
     */
    public function mentions(): MorphMany
    {
        return $this->morphMany(Mention::class, 'mentionable');
    }

    /**
     * URLs parsed from the body (ADR-F4).
     *
     * @return MorphMany<LinkedUrl, $this>
     */
    public function linkedUrls(): MorphMany
    {
        return $this->morphMany(LinkedUrl::class, 'linkable');
    }

    /**
     * Reports filed against the comment.
     *
     * @return MorphMany<PostReport, $this>
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(PostReport::class, 'reportable');
    }

    /**
     * The viewer's like on this comment — ALWAYS eager-loaded with a
     * constraint on the current actor (empty constraint for guests), so
     * listings resolve like-state without per-row queries.
     *
     * @return MorphMany<Like, $this>
     */
    public function viewerLike(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Whether this comment is a reply (depth two).
     */
    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Whether the author may still edit: inside the 15-minute window from
     * creation (D11).
     */
    public function isEditable(): bool
    {
        return $this->created_at === null
            || $this->created_at->addMinutes((int) config('feed.edit_window_minutes'))->isFuture();
    }
}
