# Fama — Feed Round 3 (Prompts 5AB – 5AI)

Based on the build at `fama.test/feed`. Run **5AB first** — the topbar stacking bug is the root cause of the worst thing in the screenshots, and the z-index scale it introduces is used by four of the prompts after it.

Paste at the top of every prompt:

> Apply the standing rules in `CLAUDE.md` and finish with the Definition of Done ritual. Use the project's existing terminology: Skills, Projects, Username, Contracts.

---

## Bugs visible in the screenshots

Folded into the prompts, listed here so nothing gets lost:

1. **The header and filter row are painting over a post frame.** The author row of the first post is completely hidden behind them. Root cause is stacking, not layout — fixed in 5AB.
2. **Mention autocomplete does not fire.** Screenshot 2 shows `@darwin-carter-7994` fully typed with no dropdown. Prompt 5U specced it; something is broken or unwired. 5AH diagnoses it.
3. **The BRAND badge truncates to `BRA…`** in the right rail. It's being allowed to shrink.
4. **Bare follower counts.** The rail cards show a lone `3` and `0` with no label or icon. Unreadable.
5. **Comment author names are uppercased.** `ELINORE O'REILLY @ELINORE-OREILLY-9363`. Display names belong in the UI face at normal case; only mono labels get uppercased.
6. **Avatars are missing everywhere** — large empty tiles with a single letter. The seeder needs images, and the fallback needs designing.
7. **PUBLISH reads as disabled even when it isn't.** Ghost text with no fill. 5AI fixes the enabled state.

---

## Prompt 5AB — Fix the topbar, introduce a z-index scale, add Feed to the nav

```
The topbar is currently painting over feed content. In the live build the page header and the sticky filter row render on top of the first post frame and hide its author row entirely. Fix the stacking properly rather than patching one z-index.

Introduce a single documented z-index scale as CSS custom properties, and replace every hardcoded z-index in the feed with a token from it. No raw z-index values anywhere after this prompt.

  --z-base: 0
  --z-frame-raised: 1
  --z-rail: 10
  --z-sticky: 20        /* the filter row */
  --z-topbar: 100
  --z-fab: 200
  --z-popover: 300
  --z-modal-backdrop: 400
  --z-modal: 410
  --z-autocomplete: 420 /* must sit above the modal, since it renders inside it */
  --z-toast: 500

Make the topbar fixed:
- position fixed, inset-block-start 0, inset-inline 0, z-index var(--z-topbar).
- Define --topbar-h as a custom property. Apply padding-block-start of that value to the page wrapper, and scroll-padding-block-start of calc(var(--topbar-h) + 16px) on html so anchor links and comment deep links do not land underneath it.
- The bar is opaque --ground. Because ground and the topbar are now the same colour after 5S, it needs a --seam hairline along its bottom edge, and that hairline should only appear once the page has scrolled past 8px. Animate its opacity, not its height.
- On viewports under 1100px the bar hides on scroll down and reveals on scroll up, matching the filter row behaviour already built in 5Y. On desktop it stays visible always.

Then fix everything that positions against it:
- The sticky filter row sticks at top var(--topbar-h) with z-index var(--z-sticky), and must be fully opaque so content scrolling under it is hidden cleanly rather than bleeding through.
- Both rails stick at calc(var(--topbar-h) + 24px) with z-index var(--z-rail), and their max-height accounts for the topbar.
- The modal backdrop covers the topbar and both rails. Right now the modal appears to sit inside the page flow; it must be portalled to the end of body.
- Frames never create their own stacking context except while hovered.

Add Feed to the topbar navigation. Order: Feed, Discover, Brands, Opportunities. The logo continues to link home. The active nav item is marked with the gestural underline stroke from the brand system, animated with stroke-dashoffset, and also carries aria-current="page" — the underline is not the only signal.

Regression test: load the feed, assert the first post's author row is visible and not overlapped, at 1440px, 1200px and 390px, in both directions.
```

---

## Prompt 5AC — Floating actions, bottom inline-start

```
Add two floating controls anchored to the bottom inline-start corner of the viewport. Inline-start, not left, so they mirror correctly in Arabic.

Stack them vertically with a 12px gap, 24px from the bottom and 24px from the inline-start edge, at z-index var(--z-fab).

Bottom control — New post:
- Only rendered for authenticated talents. Brands never see it; PostPolicy::create already rejects them.
- Guests see it and get the inline sign-in prompt used elsewhere, with intent replay through PendingIntent so the composer opens automatically after login.
- Opens the same New post modal as the top composer trigger. One component, two entry points.
- 56px circle on --surface with the frame shadow and a --seam hairline. A plus glyph in --ink. On hover the shadow lifts and the surface goes to --hover.
- Label "New post" appears as a mono pill sliding out on hover and on keyboard focus, and is always present as an aria-label.

Top control — Back to top:
- Hidden until the page has scrolled more than 1.5 viewport heights. Fades and rises 8px into place over 200ms.
- 44px circle, same treatment, slightly quieter: --ink-soft glyph rather than --ink.
- On activate, scrolls to top with smooth behaviour, and jumps instantly under prefers-reduced-motion. Moves focus to the page's first heading so keyboard and screen reader users actually go with it.

Both must not collide with anything: on viewports under 1100px they sit above the inline sign-in prompts and below the composer sheet, and they hide entirely while any modal or bottom sheet is open. Verify against the comment composer on a short post at 390px.

Use the existing IntersectionObserver scroll-motion layer for the show/hide trigger. Do not add a scroll event listener.
```

---

## Prompt 5AD — Hover system, and make the post actions legible

```
Two problems: hover feedback across the feed is inconsistent or absent, and the like, comment, share and save controls are too quiet to find.

First, define hover tokens derived from the base, alongside the tokens from 5S:

  --hover:           color-mix(in oklch, var(--surface), var(--ink) 5%);
  --hover-strong:    color-mix(in oklch, var(--surface), var(--ink) 9%);
  --hover-on-ground: color-mix(in oklch, var(--ground), var(--ink) 5%);

House rule, and enforce it everywhere: hover is never the only signal. Every hover pairs a background change with a second cue — a shadow lift, an underline drawing in, or an icon going from --ink-soft to --ink. Colour alone fails for a meaningful number of users.

Apply consistently to: frames, action buttons, filter chips, rail cards, follow and dismiss controls, nav items, mentions, link chips, comment actions, and the overflow trigger. Add every state to the /design/feed-tokens preview route: rest, hover, active, focus, disabled, loading.

Second, rebuild the post action bar. Right now it is small mono glyphs in a faint colour at the bottom of a tall frame and it disappears.

- A --seam hairline above the bar separates it from the post body.
- Each control is a real button with a minimum 40px hit height and 12px inline padding, not a bare glyph.
- Icons go up to 18px. Colour is --ink-soft at rest, --ink on hover, with a --hover background filling the button.
- Counts sit next to their icon in mono at 13px. A zero count renders the icon alone, never "0".
- Group and separate: like, comment and share sit together at the inline-start. Save and the overflow trigger are pushed to the inline-end with margin-inline-start auto. Primary actions and secondary actions should not read as one undifferentiated row.
- Liked state: the glint fills with --daylight, the count goes to --ink, and the existing 300ms shadow sweep fires. This is the one place the accent is allowed on a control.
- Saved state: the folded-corner mark fills to --ink.
- SHARE opens the share popover rather than doing nothing: copy link, share to X, WhatsApp, LinkedIn, and native share on supporting devices.
- All four controls get distinct aria-labels naming the post author, and the like and save buttons use aria-pressed.

Do the same pass on the comment action row, which has the same problem at a smaller scale.
```

---

## Prompt 5AE — Media grid geometry

```
Implement the post media grid with fixed geometry by count. The whole grid is one container at a 3:2 aspect ratio with a 4px gap, object-fit cover on every tile, and the 45-degree notch applied to the OUTER container only — never to individual tiles, which would be noise.

1 image:
  Full width, natural aspect ratio clamped between 4:5 and 16:9.

2 images:
  Two equal halves, side by side, full height each.

3 images:
  One image takes the full inline-start half at full height.
  Two images stack in the inline-end half, a quarter of the total area each.

4 or more:
  Identical to the 3-image layout — half, quarter, quarter.
  The final quarter carries an overlay: the image beneath, darkened with an --ink scrim at 55% and a 2px blur, with "+N" centred in mono at 24px in --surface, where N is the count of images not shown.

All of it uses logical properties, so the half sits on the right in Arabic.

Clicking any tile opens a lightbox at that index:
- Full screen, --ground at 96% with a backdrop blur, above everything at --z-modal.
- Previous and next controls, arrow key navigation, swipe on touch, a mono counter reading 03 / 08.
- Escape closes, focus is trapped while open, focus returns to the tile that opened it.
- Adjacent images preload. No page reload; this is Ajax like everything else.
- The post's caption and credits render beneath the image.

Every tile needs alt text. Derive it from a per-image alt field if the talent supplied one, otherwise fall back to the post body truncated, and never leave it empty.

Performance: srcset with the feed and full conversions already generated by Media Library, loading lazy on everything except the first post's media, explicit width and height attributes so nothing shifts, and contain-intrinsic-size on the grid container.

Then use the exact same grid component in the composer preview, so what the talent arranges is literally what publishes. One component, two contexts.
```

---

## Prompt 5AF — Save, report, and the overflow menu

```
Saving and reporting are currently either invisible or unfinished. Build both properly.

Overflow menu, anchored to the ... trigger in the post action bar:
- A popover, not a modal. --surface background, --seam hairline, frame shadow, 6px radius, z-index var(--z-popover).
- Positioned against the trigger, flipping above when near the viewport bottom, and repositioning on scroll and resize.
- Items for everyone: Save or Unsave, Copy link, Report post.
- Items for the author, above a --seam divider: Edit post (only while inside the 15-minute window, otherwise omitted rather than disabled), Pin to profile or Unpin, Delete post.
- Items for admins: Hide post, Remove post.
- Keyboard: arrow keys move, Enter activates, Escape closes and returns focus to the trigger. role=menu with role=menuitem children.
- Destructive items are in --danger, not red-tinted --ink.

Save feedback:
- The folded-corner mark fills to --ink immediately, optimistically.
- A toast appears at the bottom inline-end for 5 seconds: "Saved" with an Undo action and a View saved link through to the dashboard tab built in 5X.
- Unsaving shows "Removed from saved" with Undo.
- Toasts stack, cap at 3, dismiss on click, pause their timer on hover, and are announced in a polite live region.

Report dialog:
- A focused modal, not a popover, because it needs deliberation.
- Reason list as radio options, config-driven and translated into en and ar: Spam or misleading, Nudity or sexual content, Harassment or bullying, Hate speech, Impersonation, Intellectual property, Something else.
- Selecting Something else reveals a required note field, maximum 500 characters. For every other reason the note is optional.
- Submit posts to the existing report endpoint. On success the dialog is replaced in place by a confirmation panel reading that the report was received and will be reviewed, with a Close action. Do not just flash a toast; reporting deserves acknowledgement.
- One report per actor per target, enforced server-side. A second attempt shows the confirmation panel directly rather than an error.
- The same flow works on comments, with the comment's text quoted at the top of the dialog for context.

Wire the report count into the auto-hide threshold already built in 5Q.
```

---

## Prompt 5AG — Comment thread and comment composer

```
Rework how comments look and how they are written. Two separate problems.

Display. Currently the author line renders as uppercase mono, which makes names hard to read and makes every comment shout.
- Author display name: Instrument Sans 14px, --ink, normal case. Not uppercase, not mono.
- Handle: mono 11px, lowercase, --ink-faint. Never uppercased.
- Timestamp: mono 11px, --ink-faint, separated by a middot.
- The BRAND badge sits after the handle: mono 9px, --seam border, --ink-faint, with flex-shrink 0 so it stops truncating to "BRA…" as it does now.
- Avatar: 32px on top-level comments, 24px on replies, at the inline-start of the author line.
- Body: 15px --ink, with dir="auto" on each comment individually so an Arabic comment aligns to the right and an English one to the left inside the same thread. Wrap in bdi. This already works; keep it and add a test.
- Replies indent 32px inline-start with the --seam vertical rule already in place.
- Deleted comments with surviving replies render as a tombstone: --ink-faint italic on --surface-sunk.
- Load more comments and Load more replies are ghost mono buttons, not links.

Composition. The comment box is currently a large empty well that dominates the bottom of every post.
- Collapsed by default to a single 40px row showing the current actor's avatar and the placeholder. It only grows on focus.
- On focus it expands to 3 rows, reveals the SEND button, and auto-grows to a maximum of 6 rows before scrolling.
- Well is --surface-sunk with a 1px --seam border. Focus brings the border to --daylight at 1px plus a 3px --daylight ring at 14% alpha. Soft halo, not the hard 2px stroke currently there.
- padding-inline-end of 48px so browser extensions do not overlap the text.
- SEND is ghost mono while empty and fills to --ink once there is content, per the rules in 5T. It is never a saturated blue pill.
- Cmd or Ctrl plus Enter submits. Escape blurs and collapses if empty.
- Optimistic insert at the top of the thread with a subdued state, replaced by the server response, rolled back with a retry affordance on failure.
- Mention autocomplete must work in this field. See the next prompt.

Guests see the collapsed composer, and focusing it triggers the inline sign-in prompt rather than expanding.
```

---

## Prompt 5AH — Mention autocomplete is not firing; diagnose and fix

```
Mention autocomplete was specified in Prompt 5U but it does not work in the live build. A screenshot shows @darwin-carter-7994 fully typed into the New post body with no suggestion list rendered at any point.

Diagnose before changing anything. Work through, and report what you find at each step:
1. Is the Alpine component actually bound to the field? Check both the composer body and the comment field.
2. Does the @ keystroke handler fire? Is it listening on the right event — input rather than keydown, since IME and paste do not produce keydown for every character?
3. Is GET /mentions/search being called at all? Check the network path, the route registration, the middleware stack, and whether it is being blocked by auth middleware it should not be behind.
4. Does the endpoint return results for a known-good query? Test it directly.
5. If it returns results, is the dropdown rendering but invisible — clipped by overflow hidden on an ancestor, or sitting below the modal because of a z-index that predates the scale added in 5AB?

That last one is the most likely cause given the modal context. The autocomplete renders inside the modal and must sit at var(--z-autocomplete), above var(--z-modal). If any ancestor has overflow hidden or a transform, the dropdown needs to be portalled to body and positioned with a floating strategy rather than absolutely inside the field.

Once it works, verify the full behaviour from 5U in all three surfaces — composer body, comment field, reply field:
- fires after 1 character, debounced 200ms
- avatar, display name, lowercase handle, TALENT or BRAND badge
- prefix matches above substring matches, followed accounts above everyone else
- arrows move, Enter or Tab inserts, Escape closes, Enter does not submit while open
- closes on space, blur, Escape, or no matches
- caret-anchored, flipping above when short on room, full-width sheet above the keyboard under 640px
- correct anchoring for Latin handles inside Arabic text
- combobox, listbox and option roles with aria-activedescendant, result count announced

Add a browser-level test that types @dar into the composer and asserts the dropdown appears with results, so this cannot regress silently again.
```

---

## Prompt 5AI — Rebuild the New post modal

```
The composer modal is functional but unfinished. Rebuild it.

Structure:
- Portalled to the end of body at var(--z-modal), backdrop at var(--z-modal-backdrop) covering the topbar and both rails. Backdrop is --ink at 40% with a 8px blur.
- Width 640px, max-height 85vh with the body scrolling and the header and footer pinned.
- Header: the title, and beneath it the current talent's avatar, display name and lowercase handle, so it is obvious you are posting as yourself. Close control at the inline-end.
- The POST and SHARE A PROJECT toggle stays, with the active state as an --ink fill.

Body field:
- Fraunces is wrong here; this is an input, so Instrument Sans at 17px with generous line-height.
- Placeholder stays as written. Autofocus on open.
- Mention autocomplete working, per the previous prompt.
- Link chips appear inline on paste, replacing the raw URL, per 5V.
- Character counter appears at 1900 characters in --ink-faint and turns --danger at 2200.
- padding-inline-end of 48px.

Media:
- Replace the lone small plus square with a full-width dropzone: a dashed --seam border, a plus glyph, and text reading Drag images here or browse. The whole modal accepts a drop, not just the zone.
- Once files are added, render them using the real feed media grid from 5AE — half, quarter, quarter with the +N overlay. What the talent arranges is exactly what publishes.
- Each tile gets a remove control, a drag handle for reordering, and an optional alt text field revealed by a small mono ALT toggle.
- Per-file upload progress, cancel, and clear client-side validation matching the server rules.

Skills:
- Label plus a one-line hint: Tag the Skills you used on this. Chips are the talent's own Skills only. Selected state is an --ink fill.

Live credits preview:
- Beneath the skills, above a --seam divider, the credits line renders exactly as it will in the feed and updates as skills and mentions are added. This is the moment the composer earns its keep — the talent watches their editorial credit assemble as they write. Verify it is actually rendering; it is specified but not visible in the current build.

Footer:
- CLOSE as ghost mono.
- PUBLISH is currently ghost text and reads as permanently disabled. Fix: with valid content it fills to --ink with --surface text. With invalid or empty content it is --ink at 40% alpha with aria-disabled true, and hovering it shows a mono hint naming what is missing rather than doing nothing silently.
- Submitting shows an inline spinner in the button, disables the form, and on success closes the modal and optimistically inserts the frame at the top of the feed with the animation from 5Y.

Behaviour:
- Focus trap while open, focus returns to whichever trigger opened it, body scroll locked.
- Escape closes, but prompts for confirmation if there is unsaved content.
- Draft survives an accidental close within the same page session.
- Full RTL, and everything above works identically in the SHARE A PROJECT mode with the project picker and spread preview.
```

---

## Also worth fixing while you're in there

Small things from the screenshots that do not need their own prompt — add them to whichever prompt touches that surface:

- **Rail follower counts.** `3` and `0` alone mean nothing. Render as `3 FOLLOWERS` in mono, or an icon plus the number. Zero should read as `NEW` rather than `0`.
- **Avatar fallbacks.** Large empty tiles with a single letter look broken. Design the fallback: the initial in Fraunces, centred on a surface tinted deterministically from the username hash, at the same notch and crop as a real image. Then give the seeder real avatar images so you can judge the layout honestly.
- **Reason line clamping.** `Followed by @elwin-reynolds…` cuts mid-handle. Clamp to one line with an ellipsis after the full handle, or truncate the trailing count instead.

---

## Open question

The feed nav item is going in first in the topbar, ahead of Discover. That makes the feed the effective home surface of the product. If Discover should stay the front door, say so and it goes second instead — but I'd argue the feed is now the thing that brings people back daily and Discover is the thing they use with intent.
