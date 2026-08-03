# Fama — Feed Revision Batch (Prompts 5S – 5Y)

Follow-on to the Phase 5 playbook, based on the live build at `fama.test/feed`. Same rules as always.

Paste at the top of every prompt:

> Apply the standing rules in `CLAUDE.md` and finish with the Definition of Done ritual. Use the project's existing terminology: Skills, Projects, Username, Contracts.

Run order matters: **5S before 5T** (5T consumes the new tokens), everything else is independent.

---

## Before you start — one input I need

Prompt 5S grounds the entire palette in the topbar colour. I read it off the screenshot as roughly `#F4F2EE`, but **paste the exact hex from your CSS into the prompt** before running it. Every other colour is computed from that one value, so a wrong starting point propagates everywhere.

---

## Things I spotted in the screenshots worth fixing along the way

These are folded into the prompts below, but flagging them so you know they're deliberate:

1. **The credits line credits the author for every skill.** `MODELING — @DARWIN-CARTER-7994 / STYLING — @DARWIN-CARTER-7994 / WITH — @PATTIE-COLLINS-9958` is noise. The author should be credited once by their skills, and only *other* people get their own credit lines.
2. **Usernames are being uppercased.** `@DARWIN-CARTER-7994` — labels are uppercase mono, handles should stay lowercase. Uppercasing an identifier reads as shouting and makes them harder to scan.
3. **Counters appear twice** — once in the right meta rail, once in the action bar. Pick one. The rail should carry date and skills; counts belong next to the controls that change them.
4. **Project shares have no image.** Currently an eyebrow, a title, one line of text and a link. That's the weakest frame in the feed and it's supposed to be the strongest.
5. **The blue SEND and PUBLISH buttons are eating the accent.** `--daylight` was scoped to the like glint, links and focus. A large saturated blue pill on every comment box spends the accent on the least important control on the page.
6. **Textareas need `padding-inline-end`** so browser extensions (Grammarly, in your screenshots) don't sit on top of the text.

---

## Prompt 5S — Re-ground the entire palette in the topbar colour

```
The feed's palette is being re-grounded. Right now the topbar is a light neutral and the page body is a noticeably warmer, more saturated sand, which makes the body read muddy next to the bar. From now on the topbar colour is the single source of truth and every other neutral in the feed is derived from it.

Implement one base token and compute the rest with color-mix, so changing the base changes the whole feed:

:root {
  --base: #F4F2EE;   /* THE TOPBAR COLOUR - replace with the exact value from the existing topbar CSS */
  --ground:       var(--base);
  --ground-sunk:  color-mix(in oklch, var(--base), #14110B 4%);
  --surface:      color-mix(in oklch, var(--base), #FFFFFF 55%);
  --surface-sunk: color-mix(in oklch, var(--base), #14110B 2%);
  --seam:         color-mix(in oklch, var(--base), #14110B 9%);
  --ink:          color-mix(in oklch, var(--base), #14110B 92%);
  --ink-soft:     color-mix(in oklch, var(--base), #14110B 64%);
  --ink-faint:    color-mix(in oklch, var(--base), #14110B 48%);
}

[data-theme="dark"] {
  --base: #1A1815;   /* the dark-mode topbar colour, taken from the existing dark topbar */
  --ground:       var(--base);
  --ground-sunk:  color-mix(in oklch, var(--base), #000000 35%);
  --surface:      color-mix(in oklch, var(--base), #FFFFFF 6%);
  --surface-sunk: color-mix(in oklch, var(--base), #000000 18%);
  --seam:         color-mix(in oklch, var(--base), #FFFFFF 12%);
  --ink:          color-mix(in oklch, var(--base), #FFF6E8 94%);
  --ink-soft:     color-mix(in oklch, var(--base), #FFF6E8 62%);
  --ink-faint:    color-mix(in oklch, var(--base), #FFF6E8 44%);
}

Rules:
- The topbar and the page ground are now the same colour. The bar dissolves into the page; the only thing separating them is a --seam hairline, or nothing at all. This is intentional.
- Frames sit ABOVE the ground on --surface, lifted by the blue shadow, not by a border. Keep the existing shadow system and the 45-degree notch exactly as built.
- Retire --sand, --sand-deep and --chalk. Migrate every usage across the feed to the new tokens. Grep the whole codebase and leave no orphans.
- The accent is unchanged: --daylight #3D4FD6 light, #7B8AFF dark. It stays scarce. Permitted uses are ONLY: the like glint, inline links and mentions, focus rings, and the scroll light bar. It is not permitted on buttons, chip backgrounds, or filled surfaces of any kind.
- Shadows stay tinted with --daylight, never neutral grey. That rule is the identity.
- Provide static hex fallbacks in an @supports not (color: color-mix(in oklch, red, blue)) block for older browsers.

Contrast is a hard requirement, not a review note. Add a test (or an artisan command feed:check-contrast) that computes WCAG contrast ratios for the derived tokens and fails if:
  --ink on --ground or --surface is below 7:1
  --ink-soft on --ground or --surface is below 4.5:1
  --ink-faint on --ground or --surface is below 4.5:1 (it carries 12px mono meta text, which is small text)
  --daylight on --ground or --surface is below 4.5:1
Run it for both themes. If any derived value fails, adjust the mix percentage in the formula until it passes and report what you changed.

Finally, regenerate the /design/feed-tokens preview route so every token, surface, frame variant, control state and type role is visible side by side in both themes and both directions. Update docs/design-system.md.
```

---

## Prompt 5T — Comment composer and control colours

```
The comment box and the publish controls are the weakest surfaces in the feed. Fix them using the tokens from Prompt 5S.

Comment and reply input:
- Well is --surface-sunk, not the old tan. A 1px --seam border at rest.
- Focus: border becomes --daylight at 1px, plus a 3px ring of --daylight at 14% alpha. A soft halo, not a hard 2px stroke. It must still be clearly visible for keyboard users.
- Placeholder in --ink-faint. Text in --ink.
- padding-inline-end of 48px so browser extension widgets do not overlap the text.
- Auto-grow from 1 row to a maximum of 6, then scroll. No manual resize handle.

Send and Publish buttons:
- Remove the saturated blue fill entirely. The accent is reserved for the like glint, links, focus and the light bar.
- Rest with empty input: ghost. Mono uppercase, --ink-faint, transparent background, no border. Reads as unavailable without using a greyed-out disabled style.
- With content: fills to --ink with --surface text. Confident and quiet, which is the brand voice.
- Hover: 92% lightness of --ink. Active: 86%. Disabled during submit: --ink at 40% alpha plus a small inline spinner.
- Same treatment for PUBLISH in the New post modal, and for every primary action in the feed.

Comment thread surfaces:
- A comment sits directly on the frame's --surface, separated by a --seam hairline. No nested cards, no second background colour. Depth comes from indentation and hairlines only.
- Replies indent by 32px inline-start with a 1px --seam vertical rule running down the thread.
- The author's own comments get a 2px --daylight inline-start rule instead of the seam. That is the only accent permitted in the thread.
- Tombstoned comments render in --ink-faint italic on --surface-sunk.

Filter chips at the top of the feed:
- Rest: --surface-sunk background, --seam border, --ink-soft text.
- Hover: border to --ink-faint.
- Active: --ink background, --surface text. Not blue.

Then audit every remaining interactive element in the feed for rest, hover, active, focus, disabled and loading states. Any control missing a state gets one. Add them all to the /design/feed-tokens preview route.
```

---

## Prompt 5U — Mention autocomplete

```
Mentions currently have no autocomplete. Build it.

Behaviour:
- Typing @ in any post body, comment or reply field opens a suggestion list anchored to the caret.
- Query fires after 1 character, debounced 200ms, against GET /mentions/search?q= (limit 8, throttled 30/min).
- Results show avatar, display name, @username, and a mono type badge reading TALENT or BRAND. Grouped, talents first.
- Match on both username and display name, prefix matches ranked above substring matches, and people the current actor follows ranked above everyone else.
- Keyboard: arrow keys move, Enter or Tab inserts, Escape closes. While the list is open, Enter must NOT submit the form.
- Closes on space, on blur, on Escape, or when the token no longer matches anything.
- Inserting completes the token and appends a trailing space.
- Positioning: anchored to the caret, flipping above the field when there is not enough room below. On mobile it becomes a full-width sheet pinned above the keyboard.
- RTL: the list mirrors, and caret anchoring must be correct for Arabic text containing Latin usernames.
- Loading state and a "No one found" empty state, both in the feed's voice.

Wire it into all three surfaces: the New post composer, the comment field, and the reply field. One Alpine component, used three times. Do not write it three times.

Server side: /mentions/search must exclude the current actor, exclude blocked relationships if any exist, eager-load avatars, and return a single query regardless of result count. Cover with a test asserting the query count and one asserting that follow-ranking works.

Accessibility: the input gets role=combobox with aria-expanded and aria-activedescendant, the list gets role=listbox, options get role=option. Announce the result count to screen readers on each update.
```

---

## Prompt 5V — Social link chips replace raw URLs

```
When a social URL appears in a post body or a comment, the raw URL must be REPLACED by a chip showing the platform glyph plus the handle. Not appended, not shown alongside. Replaced. A long URL should never be visible in body text.

Chip anatomy: a monochrome platform glyph in currentColor, followed by the handle or resource label, in mono at 13px, on a --surface-sunk background with a --seam hairline and a 4px radius. Inline with the text, sitting on the baseline.

Labels by provider:
- Instagram profile -> @handle. Post or reel -> "Instagram post".
- TikTok profile -> @handle. Video -> "TikTok video".
- YouTube channel -> @handle. Video -> the video title once enrichment lands, "YouTube video" before that.
- Behance profile -> @handle. Gallery -> the project title, or "Behance project".
- X, LinkedIn, Pinterest, Dribbble, Vimeo, Spotify, SoundCloud, Facebook, Snapchat -> same pattern.
- Anything else -> a globe glyph plus the registrable domain.

Glyphs ship as an inline SVG sprite in the project. No external icon CDN, no icon font. Monochrome by default. On hover the glyph picks up that platform's own brand colour as a glint, the chip background lifts to --surface, and the enriched preview card fades in after a 400ms delay if enrichment succeeded. The palette stays intact at rest, which is the whole point.

Rendering happens in BodyRenderer using the persisted linked_urls and their offsets, so this works identically in the feed, on post detail, in comments, and in the mobile API payload. The API returns body_html plus a structured chips array so the mobile app can render natively.

Composer behaviour: on paste or on typing a complete URL, call POST /links/preview and swap the raw URL for the chip in the editor immediately, before enrichment returns. The chip must be correct from the URL parse alone.

Edge cases with tests: URLs with tracking query strings, mobile subdomains, trailing slashes, URLs inside Arabic text (must be bdi-wrapped), two URLs adjacent to each other, a URL at the very start and at the very end of a body, and a URL whose enrichment fails permanently.
```

---

## Prompt 5W — Richer project shares, and fix the credits line

```
Two changes to the project_share frame.

FIRST: it currently shows an eyebrow, a title, one line of description and a link. It is the weakest frame in the feed and it should be the strongest. Rebuild it as a full project preview:

- Cover image, full-bleed inside the frame, with the 45-degree notch. This is the biggest miss right now: a project share with no image.
- Eyebrow: FROM THE PROJECT, mono, --ink-faint.
- Title in Fraunces.
- A mono meta line under the title: client or brand name, year, and location if the project has one, separated by middots.
- A thumbnail strip of up to 3 further project images with a "+7" counter chip if there are more. Clicking any thumbnail goes to the project.
- Description clamped to 2 lines with a fade, not an ellipsis.
- The Skills the project covers, as mono chips.
- Collaborators credited on the project, as mention links.
- VIEW THE PROJECT -> as the explicit action.
- The whole frame is a click target to the project, with the inner mentions, chips and the action link as separate targets that do not trigger it.

Extend shared_snapshot to capture title, cover media path, client name, year, media count and skill IDs at share time, so an unpublished or deleted project degrades to a complete-looking frame marked "This project is no longer available" rather than an empty box. Update ADR-F2 in docs/decisions.md to reflect the wider snapshot.

Add a query test asserting the project share frame adds no additional queries per post when the feed is loaded, since project, cover media and skills must all be eager-loaded.

SECOND: fix the credits line. It currently reads:
MODELING - @darwin-carter-7994 / STYLING - @darwin-carter-7994 / WITH - @pattie-collins-9958

The author is credited once per skill, which is redundant. Correct behaviour:
- The author gets ONE credit line listing their skills joined by a middot: MODELING . STYLING - @darwin-carter-7994
- Every OTHER person mentioned gets their own line, labelled WITH unless the post specifies a role for them.
- Labels are uppercase mono. Handles are ALWAYS lowercase, never uppercased. Remove the text-transform currently applied to usernames.
- If there are no mentions and no skills, the credits block does not render at all. No empty rules.

Also remove the duplicated counters: like and comment counts live in the action bar only. The right meta rail carries the date and the skills. Nothing appears twice.
```

---

## Prompt 5X — Bookmarks and liked posts, with dashboard surfaces

```
Add saving to the feed, for both talents and brands, plus two new dashboard collection views.

Schema: a polymorphic bookmarks table so this extends to saving talents and projects later without a migration.
- bookmarks: id, bookmarker_type, bookmarker_id, bookmarkable_type, bookmarkable_id, created_at, unique across all four, index on (bookmarker_type, bookmarker_id, created_at)
- Only bookmarkable_type = post is enabled in this phase; the policy rejects everything else with a clear message.
- Add bookmarks_count to posts, maintained in the same transaction, covered by feed:reconcile-counters.

Action: ToggleBookmark(Actor, Bookmarkable). Idempotent, safe under double submission via the unique index. Endpoints POST and DELETE /posts/{post}/bookmark, mirrored under /api/v1. Rate limit 60/min.

UI: a Save control in the post action bar. The mark is a folded corner that echoes the frame's 45-degree notch, not a generic ribbon. Rest is outline in --ink-soft, saved is filled --ink. Label reads Save, then Saved. Optimistic with rollback. Guests get the same inline sign-in prompt as like and comment, with intent replay through PendingIntent.

Dashboard, talent side, two new tabs under the existing dashboard shell using its established layout:
- SAVED: bookmarked posts, newest first, paginated 12, rendered as compact frames with cover, author, date and skills. Empty state: "Nothing saved yet. Tap Save on any post to keep it here."
- LIKED: posts the talent has liked, newest first, same treatment. Empty state in the same voice.

Dashboard, brand side: a SAVED tab with identical behaviour.

Privacy is a decision, record it as ADR-F9: saved and liked collections are private and visible only to their owner. No public "likes" tab on profiles, and the endpoints are scoped to the current actor with no ability to request someone else's.

Both views need: server-side filtering by skill, a search field over post body, unsave and unlike inline with optimistic removal and an undo toast, eager loading with an asserted fixed query count, and full RTL support.
```

---

## Prompt 5Y — Motion: entrance, scroll direction, and light

```
Add the feed's motion layer. Reuse the existing global IntersectionObserver scroll-motion layer already built for the rest of the project. Do not write a second one, and make sure it re-initializes after every Ajax swap exactly as it does elsewhere.

Frame entrance:
- Frames rise 12px and fade from 0 to 1 over 280ms on cubic-bezier(.2,.8,.2,1), staggered 40ms within a batch, capped at 6 staggered items so a long batch does not crawl.
- Direction-aware, using the existing layer: scrolling down, frames enter from below with a 0.985 to 1 scale; scrolling up, they enter from above. Leaving the viewport reverses it, subtler, at 60% of the distance.
- Frames already in the viewport on first paint do not animate. Do not delay LCP for the sake of an entrance.

The light response, which is the signature and the reason this feed moves at all:
- As a frame passes through the middle 40% of the viewport it moves into the light: shadow blur goes 24px to 36px, y-offset 8px to 14px, and --surface lightens by 2%. It settles back as the frame leaves.
- Drive this with IntersectionObserver thresholds writing a CSS custom property. No scroll event listeners, no getBoundingClientRect in a loop.

Chrome behaviour on scroll:
- The 2px --daylight light bar at the top tracks scroll depth through the feed.
- The filter chip row is sticky. Scrolling down it condenses: the WORK . LIGHT . PEOPLE eyebrow hides, padding halves, the row shrinks to a single scrollable line. Scrolling up it expands back. 200ms, no layout jank.
- The composer trigger collapses to a compact pill on scroll down and expands on scroll up.

Other motion:
- New post optimistic insert: the frame expands from 0 height with the content fading in 80ms behind it.
- Comment thread expand and collapse animates height with the hairline seam drawing in.
- Skeletons sweep in the reading direction of the current locale.
- Like already sweeps the shadow 8px; make sure it composes correctly with the light response rather than fighting it.

Performance rules, non-negotiable:
- transform and opacity only. Nothing else animates.
- will-change applied on animation start and removed on end. Never left standing.
- content-visibility: auto with contain-intrinsic-size on offscreen frames.
- Verify with the browser performance profiler that scrolling holds 60fps with 50 frames rendered, and report the result.
- Everything above collapses to instant state changes under prefers-reduced-motion, and the page must be fully functional and readable with JavaScript disabled.
```

---

## Open questions

1. **Bookmarking talents.** The table is polymorphic and ready, but only posts are enabled. For a casting marketplace, brands saving *talents* is arguably more valuable than saving posts — it's the shortlist. Want that in this batch, or a later one?
2. **Saved collections.** Flat list for now. Named folders ("SS26 casting", "Cairo photographers") are a natural next step for brands. Not built.
3. **Public likes.** ADR-F9 makes liked and saved private. Some platforms make likes public as a social signal. Confirm you want them private.
4. **The topbar hex.** Paste the exact value before running 5S.
