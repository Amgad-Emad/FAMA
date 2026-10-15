<?php

namespace App\Services;

use App\Feed\PostPurger;
use App\Models\Brand;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Recognition;
use App\Models\Review;
use App\Models\Talent;
use App\Models\User;
use App\Support\Features;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The admin Trash: everything soft-deleted, by type — restore it, or delete it
 * forever. ONE home for the rules:
 *
 *  - restoring a post brings back the comments that went with it (same
 *    deletion time); a comment whose post is still in the trash cannot come
 *    back on its own;
 *  - deleting forever removes children no database cascade reaches
 *    (polymorphic likes / mentions / links / saves) and lets medialibrary
 *    delete the files; a talent takes their posts, comments, projects (handed
 *    to a co-owner when there is one), profile content and social graph with
 *    them.
 *
 * Every restore / purge is written to the activity log BEFORE the row can
 * disappear.
 */
class TrashService extends Service
{
    public function __construct(
        private readonly PostPurger $posts,
        private readonly TalentProjectService $projects,
    ) {}

    /**
     * The trash types this install shows (brands only with the feature).
     *
     * @return array<string, class-string<Model>>
     */
    public function types(): array
    {
        $types = ['talents' => Talent::class, 'posts' => Post::class, 'comments' => Comment::class, 'reviews' => Review::class, 'recognitions' => Recognition::class];
        if (Features::brands()) {
            $types['brands'] = Brand::class;
        }

        return $types;
    }

    /**
     * How many items sit in each type's trash.
     *
     * @return array<string, int>
     */
    public function counts(): array
    {
        return collect($this->types())->map(fn (string $class) => $class::onlyTrashed()->count())->all();
    }

    /** The trashed rows of one type, newest deletion first, with what the list shows. */
    public function query(string $type, ?string $search = null): Builder
    {
        $class = $this->types()[$type] ?? throw new InvalidArgumentException('Unknown trash type.');
        $query = $class::onlyTrashed()->latest('deleted_at');

        $query->with(match ($type) {
            'talents' => ['media'],
            'posts', 'comments' => ['author'],
            'reviews' => ['talent'],
            'recognitions' => ['talent', 'author'],
            default => [],
        });

        if ($search) {
            $query->where(fn ($w) => match ($type) {
                'talents' => $w->where('display_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"),
                'brands' => $w->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"),
                default => $w->where('body', 'like', "%{$search}%"),
            });
        }

        return $query;
    }

    /**
     * One row for the Trash list.
     *
     * @return array<string, mixed>
     */
    public function present(string $type, Model $row): array
    {
        $days = (int) config('media-library.trash_retention_days', 30);
        $excerpt = fn ($v) => str(strip_tags((string) $v))->squish()->limit(140)->toString();

        [$title, $subtitle, $avatar] = match ($type) {
            'talents' => [$row->display_name ?: $row->email, trim('@'.$row->slug.' · '.$row->email, ' ·'), $row->avatar_url],
            'brands' => [$row->name, (string) $row->email, null],
            'posts' => [$excerpt($row->body) ?: __('(photo post)'), __('Post by :name', ['name' => $row->author?->feedDisplayName() ?? '—']), $row->author?->feedAvatarUrl()],
            'comments' => [$excerpt($row->body), __('Comment by :name', ['name' => $row->author?->feedDisplayName() ?? '—']), $row->author?->feedAvatarUrl()],
            'reviews' => [$excerpt($row->body), __('Review of :name by :by', ['name' => $row->talent?->display_name ?? '—', 'by' => $row->reviewer_name]), null],
            'recognitions' => [$excerpt($row->body), __(':by → :name', ['by' => $row->author?->display_name ?? '—', 'name' => $row->talent?->display_name ?? '—']), null],
        };

        return [
            'id' => $row->getKey(),
            'title' => $title,
            'subtitle' => $subtitle,
            'avatar' => $avatar,
            'deleted_at' => $row->deleted_at?->toIso8601String(),
            'deleted_ago' => $row->deleted_at?->diffForHumans(),
            // Posts (and every file) go for good after the restore window.
            'purge_in_days' => $row->deleted_at ? max(0, $days - (int) $row->deleted_at->diffInDays(now())) : null,
            'blocked' => $type === 'comments' && Post::onlyTrashed()->whereKey($row->post_id)->exists()
                ? __('Its post is in the trash — restore the post to bring this back.') : null,
        ];
    }

    /** Restore one item (audited). */
    public function restore(User $admin, string $type, int $id): void
    {
        $row = $this->find($type, $id);

        if ($type === 'comments' && Post::onlyTrashed()->whereKey($row->post_id)->exists()) {
            throw new InvalidArgumentException(__('Restore its post first — this comment was deleted with it.'));
        }

        $this->runInTransaction(function () use ($admin, $type, $row): void {
            $deletedAt = $row->deleted_at;
            $row->restore();

            // A post's comments that went with it come back with it.
            if ($type === 'posts' && $deletedAt) {
                Comment::onlyTrashed()->where('post_id', $row->getKey())->where('deleted_at', $deletedAt)->restore();
            }

            $this->audit($admin, $row, 'trash.restored', $type);
        }, ['type' => $type, 'id' => $id]);
    }

    /**
     * Delete one item forever (audited first — the row is gone after). A null
     * admin = the automatic purge after the restore window (media:purge-trashed).
     */
    public function purge(?User $admin, string $type, int $id): void
    {
        $row = $this->find($type, $id);

        $this->runInTransaction(function () use ($admin, $type, $row): void {
            $this->audit($admin, $row, 'trash.purged', $type, ['title' => $this->present($type, $row)['title']]);

            match ($type) {
                'posts' => $this->posts->purge($row),
                'comments' => $this->purgeComment($row),
                'talents' => $this->purgeTalent($admin, $row),
                default => $row->forceDelete(), // reviews, recognitions, brands: medialibrary removes files
            };
        }, ['type' => $type, 'id' => $id]);
    }

    /**
     * One action on many trashed items. Items that can't (a comment whose post
     * is trashed) are skipped and counted.
     *
     * @param  list<int>  $ids
     * @return array{0: int, 1: int}
     */
    public function bulk(User $admin, string $type, string $action, array $ids): array
    {
        $done = 0;
        $skipped = 0;
        foreach (array_slice(array_values(array_unique($ids)), 0, 200) as $id) {
            try {
                $action === 'restore' ? $this->restore($admin, $type, (int) $id) : $this->purge($admin, $type, (int) $id);
                $done++;
            } catch (InvalidArgumentException) {
                $skipped++;
            }
        }

        return [$done, $skipped];
    }

    /** A comment, its replies and their polymorphic rows, for good. */
    private function purgeComment(Comment $comment): void
    {
        $ids = Comment::withTrashed()->where('id', $comment->getKey())->orWhere('parent_id', $comment->getKey())->pluck('id')->all();
        $this->deleteMorphRows('comment', $ids);
        $comment->forceDelete(); // FK cascades the replies
    }

    /**
     * A talent and everything they made, for good. Projects with co-owners are
     * handed over (leave) instead of deleted, so co-owners keep their work.
     */
    private function purgeTalent(?User $admin, Talent $talent): void
    {
        foreach ($talent->projects()->get() as $project) {
            $project->hasCoOwners() ? $this->projects->leave($project, $talent) : $project->delete();
        }

        // Profile content with files: Eloquent deletes so medialibrary removes them.
        foreach (['portfolioItems', 'digitals', 'showreels', 'brandCollabs', 'softwareStack'] as $relation) {
            $talent->{$relation}()->get()->each->delete();
        }

        Post::withTrashed()->where('author_type', 'talent')->where('author_id', $talent->getKey())->get()
            ->each(fn (Post $post) => $this->posts->purge($post));
        // Their comments on other people's posts (a purged parent already took its replies).
        foreach (Comment::withTrashed()->where('author_type', 'talent')->where('author_id', $talent->getKey())->pluck('id') as $commentId) {
            if ($comment = Comment::withTrashed()->find($commentId)) {
                $this->purgeComment($comment);
            }
        }

        $id = $talent->getKey();
        DB::table('likes')->where('liker_type', 'talent')->where('liker_id', $id)->delete();
        DB::table('bookmarks')->where('bookmarker_type', 'talent')->where('bookmarker_id', $id)->delete();
        DB::table('follows')->where(fn ($q) => $q->where('follower_type', 'talent')->where('follower_id', $id))
            ->orWhere(fn ($q) => $q->where('followable_type', 'talent')->where('followable_id', $id))->delete();
        DB::table('mentions')->where('mentioned_type', 'talent')->where('mentioned_id', $id)->delete();
        DB::table('notifications')->where('notifiable_type', 'talent')->where('notifiable_id', $id)->delete();

        $talent->forceDelete(); // medialibrary removes the avatar; FKs cascade the rest
    }

    /** Likes, mentions and link chips of the given posts/comments. */
    private function deleteMorphRows(string $type, array $ids): void
    {
        if ($ids === []) {
            return;
        }
        DB::table('likes')->where('likeable_type', $type)->whereIn('likeable_id', $ids)->delete();
        DB::table('mentions')->where('mentionable_type', $type)->whereIn('mentionable_id', $ids)->delete();
        DB::table('linked_urls')->where('linkable_type', $type)->whereIn('linkable_id', $ids)->delete();
    }

    /** A trashed row of a type (404 when it is not in the trash). */
    private function find(string $type, int $id): Model
    {
        $class = $this->types()[$type] ?? abort(404);

        return $class::onlyTrashed()->findOrFail($id);
    }

    /** Write the audit entry (log `moderation`). */
    private function audit(?User $admin, Model $row, string $event, string $type, array $extra = []): void
    {
        $log = activity('moderation')->performedOn($row)->withProperties(['type' => $type, 'automatic' => $admin === null] + $extra);
        ($admin ? $log->causedBy($admin) : $log)->log($event);
    }
}
