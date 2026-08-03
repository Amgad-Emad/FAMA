<?php

namespace App\Models;

use App\Models\Bookmark;
use App\Observers\PostObserver;
use App\States\Post\PostState;
use App\States\Post\Published;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\ModelStates\HasStates;

/**
 * Post — the feed's unit of content (schema-master §7, feed-spec §4).
 *
 * `author` is a morph gated to talents by PostPolicy (ADR-F1). Three types:
 * `note` (body only), `media` (1–10 items in the ordered `post_media`
 * collection), `project_share` (live-references the talent's own Project with
 * a frozen `shared_snapshot` fallback — ADR-F2). `state` drives PostState;
 * author deletes soft-delete; counters are denormalized (ADR-F6).
 */
#[ObservedBy(PostObserver::class)]
class Post extends Model implements HasMedia
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasStates, InteractsWithMedia, SoftDeletes;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'author_type', 'author_id', 'post_type', 'body', 'project_id',
        'shared_snapshot', 'state', 'published_at', 'edited_at', 'is_pinned',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'state' => PostState::class,
            'shared_snapshot' => 'array',
            'published_at' => 'datetime',
            'edited_at' => 'datetime',
            'is_pinned' => 'boolean',
            'likes_count' => 'integer',
            'comments_count' => 'integer',
            'shares_count' => 'integer',
            'bookmarks_count' => 'integer',
        ];
    }

    /**
     * The ordered post media set (1–10 images/video). Conversions are queued —
     * the feed renders WebP derivatives, originals are preserved.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('post_media');
    }

    /**
     * Feed rendering derivatives (feed-spec §4): square thumb, feed width,
     * full width — all WebP, all queued.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(320)->height(320)->format('webp')->queued();
        $this->addMediaConversion('feed')->width(1080)->format('webp')->queued();
        $this->addMediaConversion('full')->width(1600)->format('webp')->queued();

        // Video poster frame (requires ffmpeg on the worker — images are
        // unaffected; medialibrary skips the extraction for non-video files).
        $this->addMediaConversion('poster')->extractVideoFrameAtSecond(1)->format('webp')->queued();
    }

    /**
     * The publishing author (talent; brand reserved behind policy — ADR-F1).
     *
     * @return MorphTo<Model, $this>
     */
    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The shared talent-owned Project (project_share only; ADR-F2).
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Skill tags (the talent_types Skills catalog) via post_skill.
     *
     * @return BelongsToMany<TalentType, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(TalentType::class, 'post_skill');
    }

    /**
     * All comments on the post (both levels).
     *
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Top-level comments only (replies hang off Comment::replies).
     *
     * @return HasMany<Comment, $this>
     */
    public function topLevelComments(): HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id');
    }

    /**
     * Likes on the post.
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
     * URLs parsed from the body, joined to their previews (ADR-F4).
     *
     * @return MorphMany<LinkedUrl, $this>
     */
    public function linkedUrls(): MorphMany
    {
        return $this->morphMany(LinkedUrl::class, 'linkable');
    }

    /**
     * Reports filed against the post.
     *
     * @return MorphMany<PostReport, $this>
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(PostReport::class, 'reportable');
    }

    /**
     * Seen-marks for the post (feed repeat damping).
     *
     * @return HasMany<FeedSeen, $this>
     */
    public function seenMarks(): HasMany
    {
        return $this->hasMany(FeedSeen::class);
    }

    /**
     * The viewer's like on this post — eager-loaded with an actor constraint
     * (empty for guests) so lists resolve like-state without per-row queries.
     *
     * @return MorphMany<Like, $this>
     */
    public function viewerLike(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Saves of this post (5X).
     *
     * @return MorphMany<Bookmark, $this>
     */
    public function bookmarks(): MorphMany
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }

    /**
     * The viewer's save — same constrained-eager pattern as viewerLike.
     *
     * @return MorphMany<Bookmark, $this>
     */
    public function viewerBookmark(): MorphMany
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }

    /**
     * Publicly visible posts: published state with a stamped published_at.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereState('state', Published::class)->whereNotNull('published_at');
    }

    /**
     * Whether the author may still edit: inside the 15-minute window from
     * publish (D11). Draft posts are always editable.
     */
    public function isEditable(): bool
    {
        if ($this->published_at === null) {
            return true;
        }

        return $this->published_at->addMinutes((int) config('feed.edit_window_minutes'))->isFuture();
    }
}
