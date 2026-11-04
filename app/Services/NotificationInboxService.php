<?php

namespace App\Services;

use App\Feed\Actor;
use App\Models\Follow;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

/**
 * Reads for the notification inbox (feed-spec §9) — the bell popover and the
 * fama-ui notifications page share them. Owns the three page filters
 * (All / Unread / Mentions), their counts, the notification → kind mapping
 * the page picks each card's one action from, and the bulk "do I already
 * follow this follower?" lookup behind Follow back. Read-only: marking read
 * stays on the controller's single UPDATE.
 */
class NotificationInboxService
{
    /** Rows per page. */
    public const PER_PAGE = 20;

    /**
     * Notification class basename → the kind the page acts on (prototype
     * notifications.html ACT map). Anything unlisted is `system` ("Open").
     *
     * @var array<string, string>
     */
    private const KINDS = [
        'MentionedInPost' => 'mention',
        'MentionedInComment' => 'mention',
        'PostCommented' => 'comment',
        'CommentReplied' => 'comment',
        'ProjectCreditRequested' => 'credit',
        'ProjectCreditAccepted' => 'credit-ok',
        'ProjectCreditDeclined' => 'credit-ok',
        'NewFollower' => 'follow',
        'PostLiked' => 'like',
        'CommentLiked' => 'like',
        'ProjectShared' => 'like',
    ];

    /**
     * One page of the actor's inbox, newest first. $filter = 'unread' narrows
     * to unread rows, 'mention' to post/comment mentions; anything else = all.
     *
     * @return LengthAwarePaginator<int, DatabaseNotification>
     */
    public function page(Actor $actor, ?string $filter): LengthAwarePaginator
    {
        return $actor->notifications()
            ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($filter === 'mention', fn ($query) => $query->where('type', 'like', '%Mentioned%'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * The counts on the page's filter pills, in ONE aggregate query.
     *
     * @return array{all: int, unread: int, mention: int}
     */
    public function counts(Actor $actor): array
    {
        $row = $actor->notifications()
            ->reorder()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COALESCE(SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END), 0) AS unread')
            ->selectRaw("COALESCE(SUM(CASE WHEN type LIKE '%Mentioned%' THEN 1 ELSE 0 END), 0) AS mention")
            ->toBase()
            ->first();

        return [
            'all' => (int) ($row->total ?? 0),
            'unread' => (int) ($row->unread ?? 0),
            'mention' => (int) ($row->mention ?? 0),
        ];
    }

    /**
     * The kind of a notification, from its class (see KINDS).
     */
    public static function kind(string $type): string
    {
        return self::KINDS[class_basename($type)] ?? 'system';
    }

    /**
     * For the page's NewFollower rows: "type:id" of each follower => whether
     * the viewer already follows them back. One query for the whole page,
     * never per row; non-follow rows are not in the map.
     *
     * @param  Collection<int, DatabaseNotification>  $notifications
     * @return array<string, bool>
     */
    public function followedBack(Actor $actor, Collection $notifications): array
    {
        $keys = $notifications
            ->filter(fn (DatabaseNotification $n) => self::kind($n->type) === 'follow')
            ->map(fn (DatabaseNotification $n) => ($n->data['actor']['type'] ?? null).':'.($n->data['actor']['id'] ?? null))
            ->reject(fn (string $key) => str_starts_with($key, ':') || str_ends_with($key, ':'))
            ->unique()
            ->values();

        if ($keys->isEmpty()) {
            return [];
        }

        $followed = Follow::query()
            ->where('follower_type', $actor->getMorphClass())
            ->where('follower_id', $actor->getKey())
            ->where(function ($query) use ($keys): void {
                foreach ($keys as $key) {
                    [$type, $id] = explode(':', $key, 2);
                    $query->orWhere(fn ($pair) => $pair->where('followable_type', $type)->where('followable_id', $id));
                }
            })
            ->get(['followable_type', 'followable_id'])
            ->map(fn (Follow $follow) => $follow->followable_type.':'.$follow->followable_id)
            ->flip();

        return $keys->mapWithKeys(fn (string $key) => [$key => $followed->has($key)])->all();
    }
}
