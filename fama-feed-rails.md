# Fama — Feed Rails (Prompts 5Z – 5AA)

Adds the left and right sidebars: talents to follow on one side, brands on the other. Continues the Phase 5 sequence. Run 5Z before 5AA.

Paste at the top of both prompts:

> Apply the standing rules in `CLAUDE.md` and finish with the Definition of Done ritual. Use the project's existing terminology: Skills, Projects, Username, Contracts.

---

## Three decisions baked in, so you can overrule them before running

**1. Left rail is talent, right rail is brands.** Not a mixed list on both sides. The brief defines the platform as two-sided — *Side A, The Talent* and *Side B, The Local Brand* — so the two rails literally embody the two sides of the marketplace. It also means each rail can have its own card shape, its own ranking logic and its own reason lines instead of two identical columns. If you'd rather mix both types on both sides, it's a change to one service method.

**2. The per-post meta rail goes away.** Look at your screenshot: the right rail shows `27 JUN / 0 ✦ / 0 💬 / MODELING` at the top of the frame and then a tall empty column beneath it. Everything in it already appears elsewhere — the date is in the author row as `3W AGO`, the counts are in the action bar, the skills are in the credits line. It's the third duplication in the feed and it's occupying the column the brand rail needs. Per-post sticky meta also misaligns badly once frames have variable heights, which yours do by design. Killing it is a straight upgrade.

**3. `feed.allow_following_brands` has to flip to `true`.** The Phase 5 playbook shipped it as `false`. A rail of brands you can't follow is broken, so 5Z enables it. Confirm that's what you want — it means talents follow brands and brands appear in talents' feeds.

---

## Layout

```
>= 1400px  — three columns, 48px gutters

  |<-- 260 -->|<-------- 720 -------->|<-- 260 -->|
  +-----------+-----------------------+-----------+
  | TALENT    |                       | BRANDS    |
  | TO FOLLOW |    +-------------+    | TO FOLLOW |
  |           |    |   frame     |    |           |
  | [ portrait|    +-------------+    | [ square  |
  |   card   ]|                       |   card   ]|
  | [ card   ]|    +---------------+  | [ card   ]|
  | [ card   ]|    |    frame      |  | [ card   ]|
  |           |    +---------------+  |           |
  | SHOW MORE |                       | SHOW MORE |
  |  sticky   |      variable widths  |  sticky   |
  +-----------+-----------------------+-----------+

1100 - 1399px — spine + right rail only.
                Talent suggestions fall back to the inline strip.

< 1100px      — single column. Both rails become inline strips:
                talent at slot 6, brands at slot 14, alternating after.
```

Talent cards are **portrait**. Brand cards are **square**. Shape carries the type, so the two sides are distinguishable without colour and without reading a label.

---

## Prompt 5Z — Suggestion engine

```
Build the engine behind the feed's follow-suggestion rails. Server side only in this prompt; the UI is next.

First, flip config feed.allow_following_brands to true and make sure FollowPolicy, ToggleFollow, the follower and following listings and the counters all handle talent-follows-brand correctly. Add tests for that edge.

New table suggestion_dismissals: id, actor_type, actor_id, suggestible_type, suggestible_id, dismissed_at, unique across the four morph columns, index on (actor_type, actor_id). A dismissed suggestion never returns.

Build SuggestionService with two public methods, talentsFor(?Actor $viewer, int $limit) and brandsFor(?Actor $viewer, int $limit). Every candidate returned is a DTO carrying the entity, its top two Skills, its follower count, and a reason.

Ranking for talents:
- Viewer is a talent: second-degree connections first (followed by people the viewer follows), then talents mentioned in posts the viewer liked, then talents sharing a Skill with the viewer, then talents in the viewer's city, then high-signal talents overall.
- Viewer is a brand: talents matching the Skills the brand has recently filtered or searched on, then talents the brand has an existing or past contract with but does not follow, then talents in the brand's city, then high-signal overall.
- Guest: high-signal only, seeded from the session ID so it varies per visitor but is stable on refresh.

Ranking for brands:
- Viewer is a talent: brands whose Skills overlap the viewer's, then brands the viewer has an existing or past contract with, then brands followed by talents the viewer follows, then brands in the viewer's city, then active brands overall.
- Viewer is a brand: brands are not suggested to brands. Return an empty set and let the UI hide the rail.
- Guest: active brands, seeded from the session ID.

"High signal" is a config-weighted score in config/feed.php combining profile completeness, published post count in the last 30 days, follower count with diminishing returns, and account age. No magic numbers in code.

Every candidate carries a reason string, resolved in this order and translated into en and ar:
- "Followed by @handle and N others"
- "Works in Photography, like you"
- "You worked together on Project Title"
- "New in Cairo"
- "Active this week"
A suggestion with no reason still renders; the reason line is simply omitted. Never fabricate one.

Exclusions, all of them mandatory: the viewer themselves, anyone already followed, anyone dismissed, anyone unpublished or suspended, and anyone shown in the last 7 days who was not acted on. Track that last one in the cache, not in a table.

Performance: each method resolves in a fixed number of queries regardless of limit, eager-loads avatars and Skills, and caches per actor for 5 minutes with a key documented in docs/performance.md. Invalidate on follow, on dismiss, and on the viewer's own profile update. Add tests asserting the query count and asserting that every exclusion rule holds.

Endpoints, all public, all paginated by an offset cursor so Show more works:
GET  /feed/suggestions/talents
GET  /feed/suggestions/brands
POST /feed/suggestions/dismiss   { type, id }
Mirror all three under /api/v1 with Sanctum. Rate limit dismiss to 60/min. Regenerate the Scribe docs, openapi.yaml and collection.json and report the new endpoint total.
```

---

## Prompt 5AA — The left and right rails

```
Build the feed's two sidebars using SuggestionService from the previous prompt and the Daylight tokens.

Remove the per-post meta rail first. Everything in it is already elsewhere: the date is in the author row, the counts are in the action bar, the Skills are in the credits line. Delete the component and its styles, and make sure nothing else depends on it.

Layout, three columns centred as a group, 48px gutters:
- >= 1400px: left rail 260px, feed spine 720px, right rail 260px.
- 1100-1399px: spine plus right rail only. Talent suggestions fall back to the existing inline strip.
- < 1100px: single column, no rails. Both sets become inline strips, talent at feed slot 6, brands at slot 14, alternating after that.

When the rails are visible, reduce the inline strip to every 12 slots instead of every 6. Three simultaneous discovery surfaces is one too many.

Left rail, TALENT TO FOLLOW:
- Mono uppercase header in --ink-faint, a --seam hairline under it.
- Portrait cards, 4:5 crop, with the 45-degree notch, on --surface.
- Name in Fraunces 15px. Username in mono 11px, lowercase, never uppercased.
- Top two Skills as mono chips. Follower count in mono.
- Reason line in --ink-faint mono 11px, clamped to one line.
- A Follow control and a dismiss x.
- 5 cards, then SHOW MORE which appends the next 5 via Ajax with no reload.

Right rail, BRANDS TO FOLLOW:
- Same structure, square cards with the brand mark on --surface-sunk, not a portrait crop. The shape difference is how the two sides read apart without colour.
- Name in Fraunces, a mono BRAND label, sector and city in mono.
- Same reason line, Follow control and dismiss.
- Hidden entirely when the viewer is a brand, and the layout rebalances to two columns rather than leaving a gap.

Both rails are sticky with their own scroll: position sticky below the topbar, max-height calc(100vh minus offset), overflow-y auto, scrollbar hidden but scrolling functional, and they must never scroll-jack the page.

Interactions:
- Follow is optimistic. On success the button reads Following, and after 2 seconds the card slides out and the next suggestion slides up to replace it, 280ms on the house curve. An undo toast appears for 5 seconds.
- Dismiss removes the card immediately with the same slide, posts to /feed/suggestions/dismiss, and pulls a replacement. Also undoable for 5 seconds.
- Guests get the same inline sign-in prompt used for like and comment, with intent replay through PendingIntent. The rails themselves render fully for guests; this is a public page.
- Empty state: when there is nothing left to suggest, the rail collapses rather than showing a placeholder. Do not render an empty box.

Loading: the page shell renders the feed first and the rails hydrate afterwards via Ajax, so suggestions never delay the feed's first paint. Skeleton cards while loading, notched, with the light sweep in the reading direction.

RTL: the rails swap sides. Use logical properties throughout so talent stays on the inline-start side and brands on the inline-end side in both directions. No directional overrides.

Motion: rail cards use the existing global scroll-motion layer, entering with a 8px rise and fade staggered 40ms, capped at 5. They do not get the light response; that stays exclusive to feed frames so the spine remains the focus.

Accessibility: each rail is an aside with an accessible name from its header, cards are list items in a list, the Follow and dismiss controls have distinct accessible labels naming the person or brand, and follow and dismiss results are announced in a live region. Fully keyboard navigable, visible focus in --daylight.
```

---

## Open questions

1. **The viewer's own card.** A small profile card at the top of the left rail — avatar, name, follower and following counts, a link to the dashboard — is cheap and gives the rail an anchor above the fold. Not built. Want it?
2. **Brand-to-brand suggestions.** Currently returns empty and the rail hides. If brands should see peer brands, say so and it's a ranking branch.
3. **Rail width.** 260px is tight for a portrait card with a reason line. 280–300px reads better if you can spare the spine width. Worth eyeballing on the real thing before locking it.
