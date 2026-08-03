# Fama — Feed Playbook (Phase 5)

Prompt sequence for Claude Code to build the public Feed. Same format as the main build playbook: **one section = one prompt = one session**. Run top to bottom.

> **On the artifact link:** `claude.ai/public/artifacts/...` URLs render client-side and can't be fetched — same as last time. But you pasted the full Brand Identity Brief, so the design direction in §3 is derived directly from it (Playful & Elegant, Silhouettes & Lines, Texture & Detail, Movement of Light, There's Always a Chance, Gestural & Human).

---

## 0. How to use this

Every prompt below assumes:

- **The standing rules in `CLAUDE.md`** apply (senior Laravel engineer persona, SOLID, business logic in services + single-purpose Actions, Spatie Media Library for all media, Breeze multi-guard, Sanctum for mobile, mcamara + Spatie Translatable with RTL, Blade + Alpine + Ajax with no page reloads, always eager-load and paginate, docblocks above every function).
- **The per-prompt ritual / Definition of Done** applies: tests green → fail logs → DB transactions → `docs/` updated in place → `CLAUDE.md` current-state section refreshed → `README.md` accurate → **never any git write command**.
- **Terminology is locked:** *Skills* (persisted via `talent_types`), *Projects*, *Username* (the `slug` column), *Contracts*. Never write "professions", "case studies", or "campaigns".

Paste this line at the top of every prompt:

> Apply the standing rules in `CLAUDE.md` and finish with the Definition of Done ritual. Use the project's existing terminology: Skills, Projects, Username, Contracts.

---

## 1. Product decisions — locked before any code

These are answers, not questions. They go into `docs/decisions.md` in Prompt 5A so no future session re-litigates them.

| # | Decision | Ruling |
|---|---|---|
| D1 | Who can post? | **Talents only.** `posts.author` is polymorphic so brands can be enabled later by policy alone, no migration. |
| D2 | Who can like / comment / reply / follow? | **Talents and brands**, both authenticated. Admins can moderate but not participate. |
| D3 | Guests? | **Full read.** Feed, post detail, comments, profiles — all public. Any write action triggers auth. |
| D4 | Comment depth | **Two levels.** Comment → reply. A reply to a reply attaches to the same parent and auto-prefixes an `@mention`. |
| D5 | Post types | `note` (text only), `media` (1–10 images/video), `project_share` (references a Project). |
| D6 | Project share freshness | **Live reference + snapshot fallback.** Post renders the current project. A `shared_snapshot` JSON column preserves title/cover so an unpublished or deleted project degrades to a dead-but-intact frame instead of a broken post. ⚠️ This deviates from the deal-step snapshot rule on purpose — confirm with stakeholders. |
| D7 | Mention storage | Store the raw text as typed plus a `mentions` row holding **the mentioned entity's ID + character offset + length**. Renderer resolves the current Username at read time, so a username change never breaks a link. Body edit ⇒ full re-parse, offsets recomputed. |
| D8 | Link chips | Provider + handle are parsed **from the URL string itself** (zero network) so the chip renders instantly. A queued job then enriches with title/thumbnail. Instagram and similar block scraping — the chip must be useful without enrichment. |
| D9 | Feed ranking | Two tiers. **Tier 1:** posts from followed authors, last 30 days, seeded-random order. **Tier 2:** discovery (skill affinity + engagement velocity + new talents). Author diversity cap: max 2 consecutive posts from the same author. Discovery row injected every 6 items. |
| D10 | Pagination stability | The candidate ID list is computed **once per feed session**, cached against `actor + seed`, then sliced. No drift, no duplicates, no missing posts while scrolling. |
| D11 | Edit window | Posts and comments editable for **15 minutes**, then locked. An edited post shows an `edited` marker. Media set is immutable after publish. |
| D12 | Deletes | Soft delete everywhere. A deleted comment with replies renders as a tombstone so the thread survives. |
| D13 | Counters | Denormalized columns (`likes_count`, `comments_count`, `followers_count`, `following_count`, `posts_count`) maintained inside the same transaction as the write, plus a nightly reconciliation command. |
| D14 | Feed for guests and zero-follow users | Pure discovery tier, seeded by a per-visitor session seed so two refreshes don't look identical. |

---

## 2. Schema additions

Ten new tables. `docs/specs/schema-master.md` goes from **35 → 45** tables.

`posts` · `post_skill` · `comments` · `likes` · `follows` · `mentions` · `link_previews` · `linked_urls` · `post_reports` · `feed_seen`

Plus counter columns on `talents`, `brands`, `posts`, `comments`.

---

## 3. Design direction — "Daylight"

Derived from the brief. This section is the source of truth for Prompts 5L–5Q.

### The idea

The brief says the identity should feel **"constructed — like a well-cut garment, not a pattern,"** and that **light is energy and possibility**. So the feed is built like a garment laid out for cutting: pieces of different widths assembled on one centre line, each piece notched, all of them lit by a single warm light source from the top.

The whole product is lit from one direction. Surfaces are warm. **Shadows are blue, not grey** — the way daylight actually behaves. That single rule is the aesthetic risk and it's what makes the feed unmistakable at a glance.

### Signature element

**The notched frame.** Every media frame, project spread and talent card has one corner cut at 45° — a pattern-piece notch — and sits on a cool blue-tinted shadow. Nothing else in the UI is loud. That's the one accessory kept; the rest is disciplined.

### Colour

| Token | Light ("Daylight") | Dark ("Night Shoot") | Use |
|---|---|---|---|
| `--sand` | `#EDE6DA` | `#14120E` | Page ground. Warm oat with body — not cream, not white. |
| `--sand-deep` | `#E2D9C9` | `#1E1A15` | Recessed areas, input wells. |
| `--chalk` | `#FBF8F3` | `#252019` | Raised surfaces: frames, cards, composer. |
| `--ink` | `#1A1712` | `#F5F0E7` | Primary text. Warm near-black. |
| `--ink-soft` | `#5C5348` | `#A79C8C` | Meta, timestamps, captions. |
| `--daylight` | `#3D4FD6` | `#7B8AFF` | The unexpected accent. Links, active states, focus, the glint. |
| `--daylight-veil` | `#C9D0F5` | `#2A2F5C` | Washes, selected chips, skeleton sweep. |
| `--shadow` | `rgba(61,79,214,.10)` | `rgba(0,0,8,.55)` | **Blue.** Never neutral grey. |
| `--seam` | `#DBD1C0` | `#302A22` | Hairlines and dividers. |

Warm ground, cold light. Accessibility note: `--daylight` at `#3D4FD6` clears AA on `--sand` for text; the pale veil is decorative only and never carries text.

### Type

| Role | Latin | Arabic | Notes |
|---|---|---|---|
| Display | **Fraunces** (variable, `SOFT` + `WONK` axes on) | **Amiri** | The wonk axis is the "gestural & human" quality — a serif that was drawn, not engineered. Low contrast, warm. Used with restraint: post headlines, project titles, text-only notes. |
| Body / UI | **Instrument Sans** | **IBM Plex Sans Arabic** | Humanist, slightly narrow, carries long Arabic and English equally. |
| Utility | **Geist Mono** (fallback JetBrains Mono) | same | Timestamps, counters, credits labels, skill tags. Uppercase, `letter-spacing: .08em`. |

Scale: `12/13` mono meta · `15` body · `17` comments · `22–28` display for text-only notes · `34–44` for section heads. Latin numerals in both locales for all counters.

### Component language

- **Frame** — a post. Radius `6px`, one corner notched `14px` at 45°, `--chalk` surface, blue shadow, no border.
- **Variable widths** — notes `520px`, media posts `640px`, project spreads `780px`, all centred on one spine. The ragged edge is the point: pieces cut to size.
- **Credits block** — skills and mentions render as a fashion editorial credit line in mono: `PHOTOGRAPHY — @rana / STYLING — @hossam`. This is how skill tags and mentions are surfaced. It is the most on-brand detail in the whole design.
- **Glint** (like) — a four-point ✦ mark. On press, the frame's shadow sweeps ~8px as if the light source moved, 300ms. Movement of light, as an interaction. Counter digit-flips in mono. Keep the accessible label and the API as `like`; "Spark" is optional UI microcopy only.
- **Link chip** — inline pill, mono, hairline border: `[ig] @amgad`. Provider glyphs are **monochrome ink**; the provider's own colour appears only as a hover glint. Palette stays intact.
- **Casting strip** — the brand-side discovery rail, injected every 6 items. Horizontal scroll of notched talent cards: portrait crop, name in Fraunces, skills in mono. "Casting" is the correct word from this industry.
- **Light bar** — a 2px `--daylight` gradient at the top of the viewport tracking scroll depth. The only ambient motion.
- **Texture** — an SVG `feTurbulence` grain at 2.5% opacity, fixed to viewport. Rewards close inspection; invisible at a glance.

### Motion

`200–320ms`, `cubic-bezier(.2,.8,.2,1)`. Comments expand inline beneath the frame — never a modal. Skeletons are notched frames with a light sweep that runs LTR or RTL to match locale. Everything collapses to instant opacity changes under `prefers-reduced-motion`.

### Guest gating — "There's always a chance"

No blocking modal, ever. A guest tapping ✦ or Comment causes the control itself to expand inline into a lit prompt: **"Sign in to like this"** with two chips — *I'm a talent* / *I'm a brand*. Scroll position is never lost. The intent (post ID + action) is stored in session and **replayed automatically after auth**, so the like lands the moment they return. That replay is the brief's optimism made functional.

---

## 4. The prompts

### Prompt 5A — Feed spec, schema doc, decision log

```
Read docs/specs/schema-master.md, docs/specs/talent-spec.md, docs/specs/brand-spec.md and docs/decisions.md so you have full context. Write NO application code in this prompt.

Produce three documentation outputs:

1. A new canonical spec at docs/specs/feed-spec.md covering:
   - Feature summary: a public news feed where talents publish posts and share Projects, and where talents and brands follow, like, comment and reply.
   - Entity model: posts, comments, likes, follows, mentions, link previews, reports.
   - Post types: note (text only), media (1-10 images/video), project_share (references a Project).
   - Post and comment lifecycles as state machines (draft, published, hidden, removed) for spatie/laravel-model-states.
   - A full permission matrix: rows = guest / talent / brand / admin, columns = view feed, view post, create post, share project, edit own, delete own, comment, reply, like, follow, report, moderate.
   - Feed ranking specification: tier 1 followed authors (last 30 days, seeded random), tier 2 discovery, author diversity cap of 2 consecutive, discovery row every 6 items, per-session seed, cached candidate-ID pagination.
   - Mention resolution rules: store entity ID plus character offset and length; resolve Username at render time; full re-parse on edit.
   - Link chip rules: provider and handle parsed from the URL string with no network call; queued enrichment for title and thumbnail.
   - Notification matrix: which event notifies whom.
   - Moderation and reporting flow.
   - Open questions section.

2. Update docs/specs/schema-master.md in place, adding the ten new tables with every column, type, nullability, default, index and foreign key, plus the new counter columns on talents, brands, posts and comments. Update the table count from 35 to 45.

3. Append ADRs to docs/decisions.md recording these rulings, each with context, decision, consequences:
   ADR-F1 polymorphic post author but posting gated to talents by policy
   ADR-F2 project shares use live reference plus a shared_snapshot JSON fallback, deliberately differing from the deal-step snapshot rule
   ADR-F3 mentions stored as entity ID plus offset, never as username text
   ADR-F4 link provider and handle parsed from URL, enrichment is best-effort and asynchronous
   ADR-F5 feed pagination uses a cached candidate-ID list keyed by actor and session seed
   ADR-F6 denormalized counters written inside the same transaction, with a nightly reconciliation command
   ADR-F7 two-level comment depth
   ADR-F8 guest read is unrestricted; write intent is captured and replayed after authentication
```

---

### Prompt 5B — Migrations, models, states, counters

```
Implement the feed schema exactly as specified in docs/specs/feed-spec.md and docs/specs/schema-master.md.

Migrations for:
- posts: id, author_type, author_id, post_type enum(note,media,project_share), body longText nullable, project_id FK nullable, shared_snapshot json nullable, state string, likes_count, comments_count, shares_count unsigned default 0, published_at nullable, edited_at nullable, is_pinned bool, timestamps, softDeletes
- post_skill: post_id FK, talent_type_id FK, primary key both
- comments: id, post_id FK, parent_id FK nullable self, author_type, author_id, body text, likes_count, replies_count, state, edited_at, timestamps, softDeletes
- likes: id, likeable_type, likeable_id, liker_type, liker_id, timestamps, unique(likeable_type, likeable_id, liker_type, liker_id)
- follows: id, follower_type, follower_id, followable_type, followable_id, created_at, unique across all four
- mentions: id, mentionable_type, mentionable_id, mentioned_type, mentioned_id, start_offset, length, timestamps
- link_previews: id, url_hash char(64) unique, url text, canonical_url text nullable, provider string, provider_handle string nullable, provider_resource_id string nullable, title, description, image_url, embed_html, status enum(pending,ok,failed), fetched_at, expires_at, timestamps
- linked_urls: id, linkable_type, linkable_id, link_preview_id FK, position, timestamps
- post_reports: id, reportable_type, reportable_id, reporter_type, reporter_id, reason enum, note text nullable, status enum(open,reviewing,actioned,dismissed), resolved_by nullable FK users, resolved_at, timestamps
- feed_seen: id, viewer_type, viewer_id, post_id FK, seen_at, unique(viewer_type, viewer_id, post_id)

Add counter columns: talents.followers_count, talents.following_count, talents.posts_count; brands.following_count.

Indexes are mandatory, not optional:
- posts: (author_type, author_id, published_at), (state, published_at), (project_id)
- comments: (post_id, parent_id, created_at)
- likes: (likeable_type, likeable_id), (liker_type, liker_id)
- follows: (follower_type, follower_id), (followable_type, followable_id)
- mentions: (mentioned_type, mentioned_id)
- feed_seen: (viewer_type, viewer_id, seen_at)

Models: Post, Comment, Like, Follow, Mention, LinkPreview, LinkedUrl, PostReport, FeedSeen. All relationships eager-loadable. Register a Relation::enforceMorphMap in AppServiceProvider with keys: talent, brand, user, post, comment, project.

State machines with spatie/laravel-model-states: PostState (Draft, Published, Hidden, Removed) and CommentState (Published, Hidden, Removed), with allowed transitions declared.

Counter integrity: a CounterService plus observers so every counter mutation happens inside the same DB transaction as the write. Add an artisan command feed:reconcile-counters that recomputes every counter from source and reports drift. Cover it with a test that corrupts counters and asserts the command repairs them.

Add Talent::posts, Talent::followers, Talent::following, and a HasFeedIdentity trait shared by Talent and Brand.
```

---

### Prompt 5C — Actor resolution, policies, guest read

```
The app has three guards (admin on users, brand on brands, talent on talents). The feed needs a single concept of "who is acting" that works across guards and for guests.

Build:
- An Actor contract implemented by Talent and Brand, exposing feed identity (morph key, id, display name, username, avatar).
- A CurrentActor resolver service that inspects the guards in order and returns an Actor or null. Register it as a singleton and expose it to Blade via a view composer and to controllers via injection. Never call Auth::guard() directly in controllers or views again.
- Middleware resolve.actor applied to all feed routes; it must never reject guests.
- Middleware require.actor for write routes; on rejection it returns 401 with a JSON envelope containing the intended action so the frontend can render the inline sign-in prompt rather than redirecting.

Policies, registered in AuthServiceProvider:
- PostPolicy: viewAny and view allow guests; create allows talents only; update and delete allow the author within the 15-minute edit window, or any admin; pin allows the author.
- CommentPolicy: view allows guests; create allows any Actor; update and delete allow the author within 15 minutes, or any admin.
- LikePolicy and FollowPolicy: any Actor, with self-follow explicitly forbidden.
- ReportPolicy: any Actor, once per reportable per reporter.

Add a PendingIntent service: when a guest attempts a write, store {action, target_type, target_id} in the session, and after successful login replay it exactly once and clear it. Cover with feature tests for like, follow and comment replay.

Feature tests must prove: a guest can load the feed and a post detail with 200, and every write route returns 401 with the intent envelope.
```

---

### Prompt 5D — Rich text parsing: mentions and links

```
Build the parsing layer that turns a raw post or comment body into structured mentions, links and safe rendered HTML.

Value objects and DTOs with spatie/laravel-data:
- ParsedBodyData { raw, mentions: MentionData[], urls: UrlData[] }
- MentionData { mentionedType, mentionedId, username, startOffset, length }
- UrlData { url, startOffset, length }

Services:
- RichTextParser: extracts @username tokens and http/https URLs from raw text, returning byte-safe offsets. Must be multibyte-correct for Arabic. Usernames resolve against talents.slug then brands.slug; unresolved @tokens stay plain text.
- BodyRenderer: takes the raw body plus its persisted mentions and links and produces safe HTML. Escape first, then inject markup. Never trust stored HTML.

Actions:
- SyncMentions and SyncLinks, both idempotent, both called on create and on edit. On edit, delete and re-parse rather than diffing, because offsets shift.

Bidirectional text is a first-class requirement, not a polish item. Arabic body text containing Latin usernames or URLs reorders visually and breaks. Wrap every mention and every link chip in <bdi> and use Unicode isolates where raw text is emitted. Add a test with a mixed Arabic and Latin body asserting the rendered output contains the isolates in the right positions.

Rate limiting: maximum 10 mentions per post and 5 per comment, maximum 5 URLs per body. Exceeding returns a validation error naming the limit.

Add an endpoint GET /mentions/search?q= returning talents and brands matched on username and display name, limit 8, grouped by type, with avatar and username. Throttle to 30 per minute. This powers the composer autocomplete.
```

---

### Prompt 5E — Link provider registry and preview enrichment

```
Build the system that turns a pasted URL into a chip showing the provider glyph plus the handle, resource ID or page name.

Contract: LinkProvider with key(), matches(string $url): bool, parse(string $url): LinkPreviewData. parse() must work from the URL string alone with zero network calls, because the chip renders instantly and platforms like Instagram block scraping.

Drivers to implement, each with its own unit test covering profile URLs, content URLs, trailing slashes, query strings and mobile subdomains:
- Instagram: /{handle}, /p/{code}, /reel/{code}, /stories/{handle}/{id}
- TikTok: /@{handle}, /@{handle}/video/{id}
- YouTube: /watch?v=, youtu.be/{id}, /@{handle}, /shorts/{id}
- Behance: /{handle}, /gallery/{id}/{slug}
- Vimeo, X/Twitter, LinkedIn (/in/{handle}, /company/{handle}), Pinterest, Dribbble, Spotify, SoundCloud, Facebook, Snapchat
- GenericProvider: fallback, uses the registrable domain as the label

LinkProviderRegistry resolves a URL to the first matching driver, generic last. Register drivers in config/feed.php so adding one is a config line.

ResolveLinkPreview action: hash the normalized URL, find or create the link_previews row from the driver's parse output with status pending, dispatch FetchLinkPreviewJob.

FetchLinkPreviewJob: best-effort enrichment via oEmbed where available, otherwise Open Graph tags. On success set title, description, image_url, status ok, expires_at now plus 30 days. On failure set status failed and keep the parsed chip, which is already useful. Retry 3 times with backoff. Never let a failure surface as an error to the user.

Security is mandatory:
- Only http and https schemes.
- Resolve DNS and reject private, loopback, link-local and reserved ranges before connecting. This is an SSRF guard and must have its own test.
- Maximum 3 redirects, 5-second timeout, 512KB response cap.
- Strip and never render remote HTML; store embed_html only for an allowlist of providers and sanitize it.

Endpoint POST /links/preview accepting a URL, returning the chip payload, throttled to 20 per minute per actor. The composer calls this on paste.
```

---

### Prompt 5F — Post write path, project share, media

```
Build the write side for posts, all logic in Actions, all multi-write work in DB::transaction.

Actions:
- CreatePost(Actor $author, CreatePostData $data): validates, persists, attaches skills to post_skill, uploads media, calls SyncMentions and SyncLinks, transitions to Published, increments counters, dispatches notifications. Returns the Post.
- ShareProjectAsPost(Talent $talent, Project $project, ?string $body, array $skillIds): builds a project_share post, writes shared_snapshot with the project title, cover media path and shared_at, and enforces that the talent owns the project and the project is published.
- UpdatePost: enforces the 15-minute window, re-parses body, re-syncs mentions and links, sets edited_at. Media set is immutable after publish.
- DeletePost: soft delete, decrement counters, leave comments intact but hidden.
- TogglePinPost: author only, maximum 1 pinned post per talent.

Validation via form requests:
- body maximum 2200 characters
- a post must have body or media or a project; a note with neither is invalid
- 1 to 10 media items, images jpg/png/webp maximum 8MB each, video mp4/mov maximum 100MB and 60 seconds
- skills must belong to the authoring talent's own skills; reject others with a clear message

Media through Spatie Media Library only. Collection post_media with ordered items and conversions: thumb 320 square, feed 1080 wide, full 1600 wide, all WebP with the original preserved. Video gets a poster frame conversion. Register conversions as queued.

Add PostFactory and a FeedSeeder producing a realistic Egyptian and MENA dataset: about 40 talents, 15 brands, 300 posts across all three types, mentions, Instagram and Behance and TikTok links, skills attached, follows, likes and threaded comments, all with plausible Arabic and English bodies. The seeder is how the UI prompts get evaluated, so quality matters.
```

---

### Prompt 5G — Comments, replies, likes

```
Actions:
- CreateComment(Actor, Post, string $body): validates, persists, syncs mentions and links, increments post comments_count, notifies the post author.
- ReplyToComment(Actor, Comment $parent, string $body): if the parent is itself a reply, attach to the parent's parent and prefix an @mention of the reply author, so depth never exceeds two. Increment the parent's replies_count and the post's comments_count.
- UpdateComment, DeleteComment: 15-minute window; a deleted comment with replies renders as a tombstone so the thread survives.
- ToggleLike(Actor, Likeable $target): single idempotent action handling both posts and comments through the polymorphic likes table. Must be safe under double submission; rely on the unique index and handle the constraint violation rather than a read-then-write race.

Queries:
- Comment listing is paginated 10 at a time, newest first, each with up to 3 preview replies and a remaining count. Replies paginate separately.
- All listings eager-load author, mentions, links and the current actor's like state. Prove with a test asserting a fixed query count regardless of result size.

Rate limits: 10 comments per minute, 60 likes per minute, per actor. Return 429 with a friendly message.
```

---

### Prompt 5H — Follow system

```
Actions:
- ToggleFollow(Actor $follower, Actor $followable): idempotent, rejects self-follow, updates followers_count and following_count in the same transaction, notifies the followed party on a new follow only.
- Endpoints: POST and DELETE /follow/{type}/{id}; GET /talents/{username}/followers and /following, paginated 24 per page with a follow-state flag for the current actor.

Allowed edges: talent follows talent, brand follows talent. Talent follows brand is behind a config flag feed.allow_following_brands, default false.

Add a FollowService::isFollowing bulk check that takes a collection of authors and returns a keyed map, so a feed page resolves every follow button in one query. No per-row lookups anywhere.

Tests: idempotency under concurrent requests, counter accuracy, self-follow rejection, and the bulk map returning correct results for a mixed talent and brand author set.
```

---

### Prompt 5I — Feed ranking engine

```
Build FeedService, the core of the feature. All weights live in config/feed.php; no magic numbers in code.

FeedQueryData { Actor|null $viewer, string $seed, int $cursor, int $perPage }

Algorithm:
1. Resolve the candidate ID list once per feed session, then cache it for 10 minutes keyed by viewer plus seed.
   - Tier 1: published posts by followed authors from the last 30 days.
   - Tier 2: discovery — published posts scored by skill affinity with the viewer, engagement velocity over the last 48 hours, author freshness, and recency decay.
   - Guests and zero-follow viewers get tier 2 only, with the seed derived from the session ID so two visitors differ and a refresh is stable.
2. Order tier 1 by seeded shuffle, not by recency. Deterministic given the seed: order by a hash of post ID concatenated with the seed. This is the "random order for many follows" behaviour and it must be reproducible.
3. Interleave: emit tier 1 until exhausted, then tier 2, but inject one tier-2 item after every 4 tier-1 items so discovery never disappears for heavy followers.
4. Apply the author diversity cap: never more than 2 consecutive posts from the same author; displaced posts move down, they are not dropped.
5. Mark every 6th position as a casting_strip slot; the strip is resolved separately.
6. Slice the cached ID list by cursor and perPage, hydrate with a single whereIn plus orderByRaw FIELD(), eager-loading author, media, skills, mentions, links, project and the viewer's like and follow state.

The cursor is an opaque base64 token containing the seed and the offset. Pagination must be provably stable: write a test that inserts 50 posts, pages through all of them, and asserts zero duplicates and zero omissions even when new posts are created mid-pagination.

Also build DiscoverTalentsService for the casting strip: talents the viewer does not follow, ranked by skill overlap, recent activity and profile completeness, 12 per strip, never repeating within one feed session.

Performance budget: a feed page of 20 items must execute in under 25 queries and under 200ms on the seeded dataset. Add a test that asserts the query count.
```

---

### Prompt 5J — Notifications

```
Laravel notifications on the database channel, one class each: PostLiked, CommentLiked, PostCommented, CommentReplied, MentionedInPost, MentionedInComment, NewFollower, ProjectShared.

Rules:
- Never notify an actor about their own action.
- Aggregate: repeated likes on the same post within 1 hour collapse into one record with an actor count, updated in place.
- A mention notification suppresses the duplicate comment notification for the same event.
- Every notification carries a deep link to the post or comment anchor.

Endpoints: GET /notifications paginated 20, POST /notifications/read (all or by ID), GET /notifications/unread-count.

Queue every notification. Failures log and never block the originating write.

Add a bilingual test asserting notification content renders correctly in both en and ar.
```

---

### Prompt 5K — HTTP layer, routes, mobile API, docs

```
Expose the feed over web (Ajax, JSON envelope, no page reloads) and over the Sanctum-protected mobile API, sharing form requests, actions and resources between both.

Web routes, all Ajax except the two page routes:
GET  /feed                          page shell
GET  /feed/items                    paginated feed items, cursor based
GET  /feed/casting                  discovery strip
GET  /posts/{post}                  post detail page
GET  /posts/{post}/fragment         post detail as an Ajax fragment
POST /posts
PATCH/DELETE /posts/{post}
POST /posts/{post}/pin
POST /projects/{project}/share
GET/POST /posts/{post}/comments
GET/POST /comments/{comment}/replies
PATCH/DELETE /comments/{comment}
POST/DELETE /posts/{post}/like
POST/DELETE /comments/{comment}/like
POST/DELETE /follow/{type}/{id}
GET  /talents/{username}/posts
GET  /mentions/search
POST /links/preview
POST /posts/{post}/report
POST /comments/{comment}/report
GET  /notifications, POST /notifications/read, GET /notifications/unread-count

Mirror every write and list endpoint under /api/v1 with Sanctum.

Every response uses the project's existing JSON envelope. Every list is paginated and eager-loaded. API Resources: PostResource, CommentResource, ActorResource, LinkChipResource, NotificationResource, TalentCardResource — each including the viewer's interaction state so the frontend never makes a second call.

Rate limits per actor: posts 10/hour, comments 10/min, likes 60/min, follows 60/min, link previews 20/min, mention search 30/min.

Then regenerate the API artifacts: add Scribe annotations to every new endpoint, regenerate openapi.yaml and collection.json, and update docs/api.md. Report the new endpoint total against the previous 130 and list every added path.
```

---

### Prompt 5L — Design tokens

```
Implement the Daylight design system as the feed's visual foundation. It extends the existing theme; it does not replace it.

CSS custom properties on :root and [data-theme="dark"], plus matching Tailwind theme extensions:

Light: --sand #EDE6DA, --sand-deep #E2D9C9, --chalk #FBF8F3, --ink #1A1712, --ink-soft #5C5348, --daylight #3D4FD6, --daylight-veil #C9D0F5, --seam #DBD1C0, --shadow rgba(61,79,214,.10)
Dark:  --sand #14120E, --sand-deep #1E1A15, --chalk #252019, --ink #F5F0E7, --ink-soft #A79C8C, --daylight #7B8AFF, --daylight-veil #2A2F5C, --seam #302A22, --shadow rgba(0,0,8,.55)

The governing rule: shadows are blue, never neutral grey. Define --shadow-frame, --shadow-raised and --shadow-lifted all tinted with --daylight. No Tailwind default shadow utility may be used anywhere in the feed.

Typography: load Fraunces variable with SOFT and WONK axes enabled, Instrument Sans, Geist Mono. Arabic: Amiri for display, IBM Plex Sans Arabic for body and UI. Font-display swap, subset, self-hosted. Latin numerals in both locales for all counters and timestamps.

Type scale as utility classes: .t-meta 12px mono uppercase 0.08em tracking, .t-body 15px, .t-comment 17px, .t-note 24px Fraunces, .t-display 34px Fraunces.

Signature utilities:
- .frame — radius 6px, --chalk surface, --shadow-frame, and the notch: one corner cut at 45deg, 14px, on the inline-end top corner, implemented with clip-path and mirrored correctly under RTL.
- .glint — the four-point like mark plus its 300ms shadow-sweep keyframe.
- .grain — fixed viewport SVG feTurbulence overlay at 2.5% opacity.
- .credits — mono uppercase editorial credit line.
- .light-bar — 2px scroll-depth gradient in --daylight.

Everything uses CSS logical properties so RTL mirrors without a single directional override. Motion is 200-320ms cubic-bezier(.2,.8,.2,1) and every animation is disabled under prefers-reduced-motion. Focus rings are 2px --daylight at 2px offset and must be visible on every interactive element.

Deliver a /design/feed-tokens preview route rendering every token, type role, frame variant and state in both themes and both directions, so it can be reviewed at a glance. Document the system in docs/design-system.md.
```

---

### Prompt 5M — Feed shell and post frames

```
Build the public feed page in Blade with Alpine, using the shared http.js wrapper, no page reloads, and the existing IntersectionObserver scroll-motion layer.

Layout: one centre spine with variable frame widths — notes 520px, media posts 640px, project spreads 780px — all centred, producing a deliberately ragged edge. On mobile everything is full width minus 20px gutters. On desktop, a sticky right meta rail carries date, counters and skills; it mirrors to the left under RTL.

Blade components:
- x-feed.frame — the shell, with the notch and blue shadow
- x-feed.note — text-only, body set in Fraunces at 24px with generous leading, reads like a pull quote
- x-feed.media — single image full-bleed inside the frame; 2 or 3 images in an editorial grid of one large plus stacked; 4 or more as a horizontal strip with tick marks and a mono counter reading 03/08
- x-feed.spread — project share: project title in Fraunces, an eyebrow reading FROM THE PROJECT, the editorial image grid, and a link through to the project
- x-feed.credits — skills and mentions rendered as a fashion credit line: PHOTOGRAPHY — @rana / STYLING — @hossam. Skills link to filtered discovery, mentions link to profiles.
- x-feed.chip — the link chip: monochrome provider glyph plus handle, hover reveals the enriched preview card and tints the glyph with the provider's colour
- x-feed.actions — like, comment, share, and an overflow menu with report, plus edit and delete for the author
- x-feed.casting — the discovery strip, injected at every 6th slot
- x-feed.skeleton — notched frames with a light sweep that runs in the correct direction per locale

Infinite scroll via IntersectionObserver on a sentinel, fetching /feed/items with the opaque cursor. Guard against double-fetch. Preserve scroll position across all Ajax swaps. Re-initialize the scroll-motion layer after every swap, as established elsewhere in the project.

Accessibility floor: every frame is an article with a proper heading, images carry alt text from the post body or an explicit field, the strip is keyboard-navigable, and the whole page is usable with a keyboard alone.
```

---

### Prompt 5N — Composer

```
Build the post composer. It opens as a bottom sheet on mobile and a centred panel on desktop, and it is available to authenticated talents only.

Body editor, contenteditable with a plain-text model — no rich text library:
- Typing @ opens mention autocomplete against /mentions/search, debounced 200ms, keyboard navigable with arrows and Enter, showing avatar, display name and username, grouped into Talents and Brands.
- Pasting or typing a URL calls /links/preview and renders the chip inline as soon as the response lands. The chip must appear from the parsed handle even when enrichment later fails.
- Character counter appears at 1900 and turns to --daylight at 2200.
- Full RTL support with correct caret behaviour on mixed Arabic and Latin content.

Media: drag and drop plus file picker, client-side preview, reorderable by drag, per-file progress, cancel and remove, client validation matching the server rules exactly.

Skills: multi-select limited to the talent's own skills, rendered as mono chips.

Share a project: a mode that lists the talent's published projects as notched cards; selecting one switches the composer to spread preview with an optional body.

Live credits preview: as skills and mentions are added, the credits line renders live at the bottom of the composer exactly as it will appear in the feed. This is the composer's signature moment — the talent sees their editorial credit forming as they write.

On submit: optimistic insert at the top of the feed with a subdued state, replaced by the server response, rolled back with a retry affordance on failure. Draft persists in memory across an accidental close within the same page session.
```

---

### Prompt 5O — Comments, interactions, guest gating

```
Comments expand inline beneath the frame, never in a modal. A hairline seam slides in, the thread loads paginated 10 at a time with a Load more control, and each comment shows up to 3 replies with a remaining count.

Interactions:
- Like is fully optimistic: the glint fills, the counter digit-flips in mono, and the frame's shadow sweeps 8px as if the light source moved, 300ms. Rolls back cleanly on failure.
- Double-tap an image likes the post with the same flare.
- Reply pre-fills @username and focuses the field.
- Author and admin get edit and delete inline; the 15-minute edit window is shown as a quiet mono countdown.

Guest gating, the important part. No blocking modal, ever. When a guest activates any write control, that control expands in place into a lit prompt reading "Sign in to like this" with two chips: I'm a talent, I'm a brand. Scroll position is never lost. The intent is posted to the PendingIntent service, and after successful auth the user returns to the exact post anchor and the action is replayed automatically, so the like has already landed when they arrive. Cover this end to end with a feature test.

Report flow: overflow menu, reason list, optional note, confirmation toast, one report per actor per target.

All of it keyboard accessible, all live regions announced, all motion disabled under prefers-reduced-motion.
```

---

### Prompt 5P — Brand side: discovery and interaction

```
Wire the brand experience into the feed.

The casting strip, injected at every 6th feed slot, is the primary brand-side discovery surface: notched talent cards with portrait crop, name in Fraunces, skills in mono, follower count, and a follow control. Powered by DiscoverTalentsService, never repeating a talent within one feed session. Horizontally scrollable, keyboard navigable, mirrored under RTL.

Brand capabilities: like, comment, reply, follow, report, and view talent profiles from any surface. Brands cannot create posts; the composer is not rendered for them and PostPolicy::create rejects them server-side.

A brand's identity in the feed is its brand mark and name, visually distinguished from talents by a small mono BRAND label in the credits style — never by colour alone.

Add a filtered feed mode: /feed?skill={slug} restricting to posts tagged with that skill, and /feed?following=1 restricting to followed authors. Both are Ajax-swapped with no reload and both preserve the seeded ordering contract.

Add a brand-side entry point from the existing discovery page linking into the feed filtered by the skills the brand most recently searched.
```

---

### Prompt 5Q — Moderation and admin

```
Add feed moderation to the existing admin panel, matching its established layout and components.

Screens: Reports queue (filter by status, reason, target type; open a report to see the reported content in context with reporter details and author history), Posts index (search, filter by state, author and date; hide, remove, restore), Comments index (same), Talent activity view (a talent's posts, comments and reports in one timeline).

Actions: HidePost, RemovePost, RestorePost, HideComment, RemoveComment, ResolveReport — all through the state machines, all logged via spatie/laravel-activitylog with the acting admin, and all notifying the author with the reason when content is removed.

Add config-driven automatic thresholds: N open reports on one item auto-hides it pending review, default 5, and the item enters the queue flagged as auto-hidden.

Add a nightly command feed:prune that deletes feed_seen rows older than 60 days and expired failed link_previews.
```

---

### Prompt 5R — Performance, tests, close-out

```
Final hardening pass on the feed.

Performance:
- Run every feed surface through a query profiler and eliminate all N+1. Assert fixed query counts in tests for the feed page, post detail, comment thread and casting strip.
- Verify every index in the migration is actually used by the real queries; EXPLAIN the feed candidate query and the comment listing and report the results in docs/performance.md.
- Cache: the candidate ID list 10 minutes per actor and seed, the casting strip 5 minutes, link previews until expires_at. Every cache key is documented and every write path invalidates correctly.
- Media conversions and notifications are queued; confirm nothing blocks a request.

Test coverage, minimum:
- Unit: every Action, RichTextParser offsets including Arabic multibyte, every LinkProvider, the seeded shuffle determinism, the author diversity cap, counter reconciliation.
- Feature: guest read of every public route, 401 envelope on every write, pending-intent replay for like and follow and comment, full post lifecycle, two-level comment depth enforcement, edit window expiry, rate limits, SSRF rejection, bilingual rendering in en and ar.
- Pagination stability: page through 50 posts with concurrent inserts, assert zero duplicates and zero omissions.

Documentation close-out:
- Update docs/specs/feed-spec.md to match what was actually built and resolve or restate every open question.
- Update docs/architecture.md, docs/schema.md, docs/api.md, docs/design-system.md, docs/performance.md, and append to docs/changelog.md.
- Refresh the CLAUDE.md current-state section with the feed's structure, extension points and known gaps.
- Report the final endpoint count and table count against the previous 130 and 35.
```

---

## 5. Claude Design prompt — the feed page mock

Run this separately in Claude Design to get the premium HTML/CSS/JS reference the Blade prompts implement against.

```
Design and build a single-page HTML, CSS and vanilla JS prototype of a public creative feed for Fama, a curated marketplace connecting fashion brands in Egypt and the wider MENA region with creative talents: photographers, models, stylists, creative directors, cinematographers, graphic designers.

Brand personality: always exploring, confident, curious, entertaining. Playful and elegant, never heavy. The identity should feel constructed, like a well-cut garment rather than a printed pattern. Reward close inspection. Light is the brand's energy.

The concept is Daylight. The whole interface is lit from one direction: warm surfaces, and shadows that are blue rather than grey, the way daylight actually behaves. That single rule is the aesthetic risk and it should be visible everywhere.

Signature element: the notched frame. Every media frame, project spread and talent card has one corner cut at 45 degrees, like a pattern piece, sitting on a soft blue-tinted shadow. Nothing else is loud.

Palette. Light: sand #EDE6DA, sand-deep #E2D9C9, chalk #FBF8F3, ink #1A1712, ink-soft #5C5348, accent daylight #3D4FD6, veil #C9D0F5, seam #DBD1C0, shadows tinted with the accent. Dark: sand #14120E, chalk #252019, ink #F5F0E7, accent #7B8AFF. Include a theme toggle.

Type. Display: Fraunces variable with SOFT and WONK axes on, used with restraint. Body and UI: Instrument Sans. Utility: Geist Mono, uppercase with 0.08em tracking for timestamps, counters, credits and skill tags. Include an Arabic RTL toggle using Amiri for display and IBM Plex Sans Arabic for body, and mirror the entire layout including the notch.

Layout: one centre spine with deliberately variable frame widths, notes at 520px, media posts at 640px, project spreads at 780px, producing a ragged edge that reads as pieces cut to size. Sticky meta rail on desktop. Full width on mobile.

Build these post variants with realistic Egyptian and MENA content, not lorem ipsum: a text-only note set large in Fraunces; a single-image post; a three-image editorial grid; an eight-image horizontal strip with tick marks and a 03/08 mono counter; a project share spread with an eyebrow reading FROM THE PROJECT.

Every post ends with a credits block set as a fashion editorial credit line in mono: PHOTOGRAPHY — @rana / STYLING — @hossam. This is how skill tags and mentions are surfaced and it should be the most on-brand detail on the page.

Link chips appear inline in post bodies and comments: a monochrome provider glyph plus the handle, for example an Instagram glyph followed by @amgad. On hover the glyph picks up the provider's own colour as a glint and a small preview card appears. Include Instagram, TikTok, YouTube and Behance examples.

Interactions to build: a four-point glint mark for likes that, when pressed, sweeps the frame's shadow about 8px as if the light source moved, over 300ms, while the counter digit-flips in mono. Comments expand inline beneath the frame, never in a modal, with threaded replies one level deep. A horizontal casting strip of notched talent cards injected every six posts. A 2px scroll-depth light bar at the top.

Guest state is important: this page is public, but every write action requires sign-in. When a signed-out visitor taps like or comment, the control itself expands in place into a lit prompt reading "Sign in to like this" with two chips, I'm a talent and I'm a brand. No blocking modal, no lost scroll position.

Also build the composer as a centred panel: body field with @mention autocomplete, a live link chip on paste, media drag-and-drop with reorder, skill multi-select, and a live credits preview that assembles as the talent types.

Add a fixed SVG grain overlay at 2.5% opacity. Motion is 200 to 320ms on cubic-bezier(.2,.8,.2,1), fully disabled under prefers-reduced-motion. Visible keyboard focus in the accent colour on every interactive element. Fully responsive to 375px.

Deliver as a single self-contained HTML file with Tailwind via CDN plus a custom style block for the tokens, the notch clip-path and the shadow system. Everything interactive should actually work against in-memory mock data.
```

---

## 6. Open questions for you

1. **ADR-F2** — project shares live-reference the Project rather than snapshotting it, which deliberately differs from the deal-step snapshot rule. Confirm before 5F.
2. **Can brands post?** Locked to no. The schema supports flipping it with a policy change alone if that's wrong.
3. **Video** — currently in scope at 60s / 100MB. That's real storage and transcoding cost. Say the word and I'll cut it to images-only for v1 and add it back in a later phase.
4. **"Spark" vs "Like"** — the glint mark is the visual; the label stays "Like" in code, API and accessibility. If you want "Spark" as the user-facing word, it's a copy change in one language file.
5. **Trending / explore tab** — not in this phase. The ranking service is built to support it later.
6. **Hashtags** — deliberately excluded. Skills already do that job and do it better for a curated marketplace. Flag it if you disagree.
