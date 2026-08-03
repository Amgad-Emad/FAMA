# Fama — Feed Spec (Entities · Ranking · Permissions · Lifecycles)

> Canonical description of the public Feed (Phase 5). Pair with `schema-master.md` §7 (the ten feed
> tables) and `docs/decisions.md` ADR-F1…F8 (the locked rulings). Terminology is the project's:
> **Skills** (persisted via `talent_types`), **Projects** (the talent-owned `projects` table),
> **Username** (the `slug` column), **Contracts**.

---

## 1. Feature summary

A public news feed where **talents publish posts** — text notes, media sets, and shares of their own
Projects — and where **talents and brands** follow authors, like, comment and reply. **Guests read
everything**; any write action triggers authentication, captures the intended action, and replays it
after login (ADR-F8). Admins moderate but never participate.

The feed is the discovery surface between the profile (identity) and the contract room (transaction):
a brand scrolling the feed meets talents through their work, follows them, and steps into a profile —
and from there into a contract.

## 2. Actors

| Actor | Guard | Can |
|---|---|---|
| Guest | none | read everything |
| Talent | `talent` → `talents` | post, share own Projects, follow, like, comment, reply, report |
| Brand | `brand` → `brands` | follow, like, comment, reply, report — **not post** (ADR-F1) |
| Admin | `admin` → `users` | moderate (hide/remove/restore, resolve reports) — **not participate** |

`posts.author` and every actor-side morph (`liker`, `follower`, `comment.author`, `reporter`,
`viewer`) are **polymorphic** so brands can be enabled as authors later by policy alone — no
migration (ADR-F1). A single `Actor` contract (implemented by `Talent` and `Brand`) + a
`CurrentActor` resolver give the feed one concept of "who is acting" across the three guards.

### Permission matrix

| | view feed | view post | create post | share Project | edit own | delete own | comment | reply | like | follow | report | moderate |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| **Guest** | ✅ | ✅ | intent→auth | intent→auth | — | — | intent→auth | intent→auth | intent→auth | intent→auth | intent→auth | — |
| **Talent** | ✅ | ✅ | ✅ | ✅ own, published profile | ✅ 15 min | ✅ | ✅ | ✅ | ✅ | ✅ talents | ✅ once/target | — |
| **Brand** | ✅ | ✅ | ❌ (policy) | ❌ | ✅ 15 min (comments) | ✅ (comments) | ✅ | ✅ | ✅ | ✅ talents | ✅ once/target | — |
| **Admin** | ✅ | ✅ | ❌ | ❌ | — | — | ❌ | ❌ | ❌ | ❌ | — | ✅ |

- "intent→auth" = the write is rejected with a 401 JSON envelope carrying the intended action; the
  frontend renders the inline sign-in prompt and the `PendingIntent` service replays the action once
  after successful auth (ADR-F8).
- "edit own" is bounded by the **15-minute edit window** (D11): posts and comments lock after
  15 minutes from `published_at` / `created_at`; an edit stamps `edited_at` and shows an `edited`
  marker. **The media set is immutable after publish** — only the body/skills re-open.
- Admins may edit/delete **any** post or comment through moderation actions (state machine +
  activity log), never through the participant endpoints.
- Follow edges: **talent → talent** and **brand → talent**. Talent → brand sits behind
  `feed.allow_following_brands` (config, default `false`). Self-follow is forbidden.

## 3. Entity model

Ten tables (full column definitions in `schema-master.md` §7):

| Table | Purpose |
|---|---|
| `posts` | The post itself: polymorphic author, `post_type`, body, optional Project reference + `shared_snapshot`, state, denormalized counters |
| `post_skill` | Post ⇄ Skill tags (`talent_types`) — powers skill affinity and `/feed?skill=` |
| `comments` | Two-level thread (comment → reply via `parent_id`), polymorphic author, own counters |
| `likes` | Polymorphic both sides: `likeable` (post/comment) × `liker` (talent/brand), unique per pair |
| `follows` | Polymorphic both sides: `follower` × `followable`, unique per pair |
| `mentions` | `mentionable` (post/comment) → `mentioned` (talent/brand) + `start_offset` + `length` (ADR-F3) |
| `link_previews` | One row per unique URL (`url_hash`): parsed provider/handle instantly, enriched async (ADR-F4) |
| `linked_urls` | `linkable` (post/comment) → `link_previews`, positioned |
| `post_reports` | Reports on posts **and** comments (`reportable`), reason + status + resolver |
| `feed_seen` | Viewer × post seen-marks — feeds discovery freshness; pruned after 60 days |

Counter columns (ADR-F6): `posts.likes_count/comments_count/shares_count`,
`comments.likes_count/replies_count`, `talents.followers_count/following_count/posts_count`,
`brands.following_count`. (`brands.followers_count` now exists — `feed.allow_following_brands` is
`true`.) **`talents.last_posted_at`** is a denormalized timestamp (not a count) maintained by the same
`CounterService` publish-boundary path and reconciled nightly with a NULL-safe `<=>` — it powers the
dashboard "quiet since" prune signal (Prompt 5-follow).

**Morph map:** the feed enforces `Relation::enforceMorphMap` with keys `talent`, `brand`, `user`,
`post`, `comment`, `project` — aliased, never FQCNs. Implementation must first verify the existing
contract-engine morphs (`contract_steps.completed_by_*`, `contract_messages.sender_*`) and include
their stored keys in the map so enforcement doesn't break in-flight data.

## 4. Post types (D5)

| `post_type` | Body | Media | Project | Rendering |
|---|---|---|---|---|
| `note` | required, ≤ 2200 chars | — | — | Large display type; reads like a pull quote |
| `media` | optional caption | 1–10 images/video (`post_media` collection) | — | Editorial grid / strip |
| `project_share` | optional | — (the Project's cover renders) | required, own + visible | Project spread with "FROM THE PROJECT" eyebrow |

A post must have **body or media or a Project** — an empty note is invalid.

**Project shares (ADR-F2)** live-reference the current `projects` row so the spread always shows the
Project as it is *now*. At share time a `shared_snapshot` JSON captures `{title, cover_media_path,
shared_at}`; if the Project is later deleted (or its profile unpublished), the post degrades to a
**dead-but-intact frame** rendered from the snapshot — never a broken post, never a leak of content
the owner withdrew (the frame shows the snapshot title/cover but stops linking through).
⚠️ This deliberately deviates from the contract-step snapshot-everything rule — flagged for
stakeholder confirmation (open question Q1).

Media go through **spatie/laravel-medialibrary** only: collection `post_media`, ordered, with queued
conversions `thumb` (320² WebP), `feed` (1080w WebP), `full` (1600w WebP), original preserved; video
gets a poster-frame conversion. Limits: images jpg/png/webp ≤ 8 MB; video mp4/mov ≤ 100 MB and
≤ 60 s (open question Q3).

## 5. Lifecycles (spatie/laravel-model-states)

**PostState** — column `posts.state`:

```
Draft → Published        (publish: stamps published_at, increments posts_count)
Published → Hidden       (moderation, or auto-hide at the report threshold)
Hidden → Published       (restore)
Published|Hidden → Removed (moderation removal — terminal; author notified with reason)
```

Soft delete (`deleted_at`) is orthogonal: an author's delete soft-deletes the row (D12) and
decrements counters; moderation uses the state machine so the row stays queryable in admin.

**CommentState** — column `comments.state`:

```
Published → Hidden → Published   (moderation hide/restore)
Published|Hidden → Removed       (terminal)
```

Comments are born `Published` (no draft stage). A deleted/removed comment **with replies** renders
as a **tombstone** — the thread survives (D12).

**Report status** (plain enum, not a state machine): `open → reviewing → actioned | dismissed`.

## 6. Feed ranking (ADR-F5, D9/D10/D14)

`FeedService` resolves a **candidate ID list once per feed session**, caches it 10 minutes keyed by
`viewer + seed`, then slices it per page. All weights live in `config/feed.php`.

1. **Tier 1 — followed:** published posts by authors the viewer follows, last 30 days, ordered by a
   **seeded shuffle** — `hash(post_id . seed)` — deterministic given the seed, reproducibly "random".
2. **Tier 2 — discovery:** published posts scored by **skill affinity** with the viewer (shared
   Skills between the post's tags and the viewer's Skills / a brand's creative needs), **engagement
   velocity** (likes+comments over the last 48 h), **author freshness** (new talents get a boost),
   and **recency decay**.
3. **Interleave:** tier 1 until exhausted, then tier 2 — but one tier-2 item is injected after every
   4 tier-1 items so discovery never disappears for heavy followers.
4. **Author diversity cap:** never more than 2 consecutive posts from one author; displaced posts
   move down, never dropped.
5. Every **6th position** is a `casting_strip` slot — the brand-side discovery rail (12 talents the
   viewer doesn't follow, ranked by skill overlap + recent activity + profile completeness, never
   repeating within a feed session; resolved by `DiscoverTalentsService`, cached 5 minutes).
6. **Hydration:** slice the cached ID list by cursor, hydrate with one `whereIn` +
   `orderByRaw FIELD()`, eager-loading author, media, skills, mentions, links, Project, and the
   viewer's like/follow state. Budget: ≤ 25 queries, < 200 ms per 20-item page.

**Guests and zero-follow viewers** (D14) get tier 2 only, seeded from the session ID — two visitors
differ, one visitor's refresh is stable.

**Cursor:** an opaque base64 token `{seed, offset}`. Because the ID list is frozen per session,
paging is provably stable — no duplicates, no omissions, even when posts are created mid-scroll.
`feed_seen` records viewer × post exposure to damp repeats across sessions; pruned after 60 days.

## 7. Mentions (ADR-F3, D7)

- Stored as **entity ID + `start_offset` + `length`** against the raw body — never as username text.
  The renderer resolves the **current** Username at read time, so a Username change never breaks a
  mention.
- `RichTextParser` extracts `@username` tokens with **multibyte-correct** offsets (Arabic bodies);
  usernames resolve against `talents.slug` then `brands.slug`; unresolved tokens stay plain text.
- **Edit ⇒ full re-parse** — delete and recreate mention rows; offsets shift, diffing is not
  attempted.
- Rendering escapes first, then injects markup; every mention and link chip is wrapped in `<bdi>`
  with Unicode isolates so mixed-direction bodies never reorder.
- Limits: 10 mentions per post, 5 per comment; exceeding is a validation error naming the limit.

## 8. Link chips (ADR-F4, D8)

- Provider + handle/resource are parsed **from the URL string alone — zero network** — via a
  `LinkProvider` driver registry (`config/feed.php`): Instagram, TikTok, YouTube, Behance, Vimeo,
  X/Twitter, LinkedIn, Pinterest, Dribbble, Spotify, SoundCloud, Facebook, Snapchat, generic
  fallback (registrable domain as label). The chip renders instantly from the parse.
- `link_previews` is deduplicated by `url_hash` (SHA-256 of the normalized URL). A queued
  `FetchLinkPreviewJob` enriches best-effort (oEmbed, then Open Graph): success → title/description/
  image, `status=ok`, `expires_at = +30 days`; failure → `status=failed`, the parsed chip stays
  useful. 3 retries with backoff; a failure never surfaces to the user.
- **SSRF guard is mandatory:** http/https only; DNS resolved and private/loopback/link-local/
  reserved ranges rejected before connecting; ≤ 3 redirects, 5 s timeout, 512 KB cap; remote HTML
  never rendered; `embed_html` only for an allowlist of providers, sanitized.
- Limit: 5 URLs per body.

## 9. Notifications (database channel, queued)

| Event | Class | Notifies | Notes |
|---|---|---|---|
| Post liked | `PostLiked` | post author | aggregates: repeat likes on one post within 1 h collapse into one record with an actor count |
| Comment liked | `CommentLiked` | comment author | same aggregation |
| Post commented | `PostCommented` | post author | suppressed when the same event already produced a mention notification for the author |
| Comment replied | `CommentReplied` | parent-comment author | |
| Mentioned in post | `MentionedInPost` | mentioned talent/brand | wins over the comment/post notification for the same event |
| Mentioned in comment | `MentionedInComment` | mentioned talent/brand | |
| New follower | `NewFollower` | followed talent | on a **new** follow only — unfollow/refollow doesn't re-notify within the aggregation window |
| Project shared | `ProjectShared` | the author's followers | the one fan-out notification: a followed talent sharing a Project is milestone content (open question Q5) |

Rules: never notify an actor about their own action; every notification carries a deep link to the
post/comment anchor; failures log and never block the originating write; content renders in both
`en` and `ar`.

## 10. Moderation & reporting

- Any Actor reports a post or comment (`post_reports`, polymorphic `reportable`): reason is a
  **config-driven key** (`feed.report_reasons`, round 3: `spam`, `nudity`, `harassment`, `hate`,
  `impersonation`, `ip`, `other` — column widened to string(40) by migration 2026_07_22_000600,
  legacy rows mapped) + note (**required** when reason = `other`, ≤500); one report per reportable
  per reporter. The client dialog confirms receipt honestly — including on the duplicate 403.
- **Auto-hide threshold** (config `feed.auto_hide_reports`, default 5): N open reports on one item
  transitions it to `Hidden` and flags it *auto-hidden* in the queue.
- Admin surfaces (existing admin panel patterns): Reports queue (filter status/reason/target type;
  report opens the content in context with reporter details + author history), Posts index, Comments
  index, Talent activity timeline.
- Actions `HidePost / RemovePost / RestorePost / HideComment / RemoveComment / ResolveReport` — all
  through the state machines, all activity-logged with the acting admin, removal notifies the author
  with the reason.
- Housekeeping: nightly `feed:prune` deletes `feed_seen` rows > 60 days and expired failed
  `link_previews`; nightly `feed:reconcile-counters` recomputes every denormalized counter from
  source and reports drift (ADR-F6).

## 11. Guest gating & pending intent (ADR-F8)

No blocking modal, ever. A guest activating any write control gets the control itself expanded
inline into a sign-in prompt ("Sign in to like this" — *I'm a talent* / *I'm a brand*), scroll
position intact. The rejected write returns **401 with the JSON envelope carrying
`{action, target_type, target_id}`**; the `PendingIntent` service stores that intent in the session
and, after successful login, **replays it exactly once** and clears it — the user returns to the
post anchor with the action already landed.

## 12. HTTP surface

Web (Ajax, shared envelope, no reloads) + `/api/v1` mirror under Sanctum, sharing form requests,
Actions and Resources. Full route table in `docs/api.md` once built (Prompt 5K). Rate limits per
actor: posts 10/h, comments 10/min, likes 60/min, follows 60/min, link previews 20/min, mention
search 30/min.

### Follow relationships — public vs dashboard (Prompt 5-follow)

The follow graph has **two read surfaces with different payloads**:

- **Public listing** (`GET /talents/{username}/followers|/following`, from Prompt 5H): actor identity
  + the viewer's own `followed_by_viewer` state, paginated 24. This is the shareable, unauthenticated
  view — it carries NO relationship intelligence about the profile owner.
- **Dashboard management** (`GET /{talent|brand}/feed/follows[/data]`, Prompt 5-follow): the
  authenticated actor's OWN graph, shaped for pruning. FOLLOWING (accounts they follow, inline
  optimistic unfollow) and FOLLOWERS (FOLLOW BACK where not mutual) sub-tabs; mixed-type cards whose
  SHAPE carries the type (portrait talents, square brands — no colour distinction); each row adds the
  dashboard-only signals **FOLLOWS YOU BACK** (mutual) and **QUIET SINCE <month>** (`last_posted_at`
  > 90 days). Controls: server-side search (name + Username), a talents/brands type filter (hidden for
  brands), a Skill filter drawn from Skills present in the set, and sorts (recently followed /
  alphabetical / least-active). The mutual badge and quiet-since signal are dashboard-only and **must
  not leak** into the public listing (asserted). Eager loading uses `MorphTo::morphWith` (fixed query
  count); mutuality is one bulk `FollowService` query, never per row.

## 13. Open questions (close-out status, 5R)

- **Q1 (ADR-F2) — OPEN, stakeholder call:** Project shares live-reference + snapshot-fallback,
  deliberately differing from the contract-step snapshot rule. **Built as specified**; flipping to
  snapshot-everything would be a render-path change only.
- **Q2 — RESOLVED as built:** sharing gates on the owning profile being published
  (`ShareProjectAsPost`); a talent Project has no publish state of its own.
- **Q3 — OPEN, product call:** video shipped at 60 s / 100 MB (size caps always enforced; duration
  via ffprobe when present; poster conversion needs ffmpeg on the worker). Cutting to images-only
  is a validation + composer-accept change.
- **Q4 — RESOLVED:** the code, API and accessible labels say `like` everywhere; "Spark" remains a
  one-line lang swap if wanted.
- **Q5 — RESOLVED as built:** `ProjectShared` fan-out to followers is live (chunked); cutting it is
  one class + one notifier call.
- **Q6 — RESOLVED:** hashtags excluded; Skills carry topical navigation (`/feed?skill=`).
- **Q7 — OPEN by design:** trending/explore not built; `FeedService` tier-2 scoring is the engine
  it would reuse.
