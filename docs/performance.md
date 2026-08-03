# Performance

> Feed performance contract (Phase 5R). Budgets are enforced by tests; indexes are verified by
> EXPLAIN against the seeded dataset (300 posts / 549 comments / 1 782 likes). Update in place when
> a budget or index changes.

## Query budgets (test-enforced)

| Surface | Budget | Test |
|---|---|---|
| Feed page of 20 items (`/feed/items`) | **< 25 queries** | `FeedRankingTest` |
| Comment thread listing | **fixed count, size-independent** (same-shape threads at different sizes execute identical query counts) | `CommentThreadTest` |
| Post detail page | < 15 queries | `HardeningSweepTest` |
| Casting strip (warm cache) | < 10 queries | `HardeningSweepTest` |

The feed page hydrates its slice with **one `whereIn` + `FIELD()`** and eager-loads the full graph
(author.media, media, skills, project.media, mentions.mentioned, linkedUrls.preview, constrained
`viewerLike`); the viewer's like/follow state always resolves in bulk (`viewerLike` eager
constraint, `FollowService::isFollowing` keyed map) — never per row. `preventLazyLoading` is on in
non-production, so any regression throws.

## EXPLAIN evidence (MySQL, seeded dev DB, 2026-07-22)

Feed candidate query (tier 1 — followed authors, last 30 days):

```
posts · key=posts_author_type_author_id_published_at_index · type=range · rows≈15
```

Comment listing (top-level, newest first):

```
comments · key=comments_post_id_parent_id_created_at_index · type=ref · rows≈4
```

Likes counter recompute (reconcile / likeable lookups):

```
likes · key=likes_likeable_type_likeable_id_index · type=ref · rows≈2
```

Every index declared in `2026_07_22_000100_create_feed_tables.php` serves a real query: the two
`posts` composites (candidate windows + state scans), the `comments` composite (threads), both
`likes`/`follows` composites (viewer state + audiences + reconcile), `mentions.mentioned` (rename-
safe rendering + notifications), `feed_seen (viewer, seen_at)` (pruning + repeat damping),
`link_previews.url_hash` unique (dedupe).

## Cache inventory

| Key | TTL | Invalidation |
|---|---|---|
| `feed:candidates:{viewer\|guest}:{seed}:{skill\|-}:{a\|f}` | 10 min | **Deliberately none on write** — the frozen list IS the pagination-stability contract (ADR-F5/D10); new posts join the next session. |
| `feed:casting:{viewer\|guest}` | 5 min | TTL only; stores ranked **IDs**, never models (serialized Eloquent breaks across opcache states). |
| `feed:casting-shown:{viewer\|guest}:{seed}` | 10 min | TTL — the no-repeats-per-session set. |
| `link_previews` rows | `expires_at` (+30 days on `ok`) | `feed:prune` deletes failed+expired+unreferenced rows nightly. |
| `feed:suggestions:{talents\|brands}:{morph-id\|guest:seed}` | 5 min | forgotten on follow, on dismiss, and on the viewer's own profile update (5Z); ranked `{id, reason}` arrays only — never models. |
| `feed:suggestions-shown:{type}:{morph-id\|guest}` | 7 days | the shown-but-unacted rest set (cache, not a table). |
| Rate limiters (`feed-*`) | window-scoped | framework-managed. |

## Queues & scheduled work

- **Queued, never blocking a request:** post media conversions (thumb/feed/full WebP + video
  `poster` — poster requires **ffmpeg** on the worker), `FetchLinkPreviewJob` (3 tries, backoff,
  SSRF-guarded), all feed notifications (`afterCommit`). `QUEUE_CONNECTION=database`; run
  `php artisan queue:work`.
- **Scheduled:** `feed:reconcile-counters` 03:10 (counter drift self-heals + logs),
  `feed:prune` 03:40.
- Operational note: video **duration** validation shells to ffprobe only when the binary exists;
  the size caps always bind (`PostMediaFile`).
