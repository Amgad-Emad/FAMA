# Design system

Fama runs **two token systems**, both keyed on the same `data-theme` (light/dark) and `dir`
(LTR/RTL) switches:

1. **Fama core** (`resources/css/app.css`) — the official FAMA brand system (FAMA — BRANDING PHASE 3
   PDF, adopted 2026-08-11): **warm cream ground** (`--bg #FAF6E0` / `--surface #FDFBEF` — never
   cool/blue-grey), warm-plum ink, and **primary Orchid Purple `#AD50D3`** (R173 G80 B211;
   `--accent`; wash = brand lavender `#F7E0FF`; `--accent-ink` darkened `#8B2FA8` for 4.5:1+ text).
   Brand secondaries fill existing roles: **yellow `#FFEB73`** as `--gold-weak` (star/Primary-badge
   wash; `--gold #7E6600` for WCAG text on it) and **mint `#6CEAC2`** tinting the success family
   (`--success #0B7059` / `--success-weak #BDF4E3`). The dark theme is a plum night re-derivation.
   **Blue is banned everywhere** — including the demo-image generator
   (`GeneratesCoverImages` constrains hues to the brand arc, circles stay in the ground's family).
   Text-role variants are always darkened/lightened for contrast; raw brand values live in fills
   and washes. Bricolage Grotesque + Sora + IBM Plex Sans Arabic + IBM Plex Mono. Used by every
   surface except the feed. Documented inline in `app.css`; restyling is token-only.
2. **Daylight** (`resources/css/daylight.css`) — the feed's system (Phase 5, playbook §3).
   **Feed-scoped**: everything inside a `[data-surface='daylight']` node.

## Daylight — the idea

The feed is built like a garment laid out for cutting: pieces of different widths on one centre
spine, each piece notched, all lit by a single warm light source. **Warm ground, cold light —
shadows are blue (`--dl-shadow`, tinted with the accent), never neutral grey.** No Tailwind default
shadow utility appears anywhere in the feed.

## Scoping model

`[data-surface='daylight']` re-reads the CORE tokens (`--bg`, `--surface`, `--ink`, `--muted`,
`--accent`, `--line`, `--font-*`, …) from the Daylight palette. Inside the scope every existing
utility (`bg-surface`, `text-ink`, `border-line`, components…) renders Daylight automatically;
outside it nothing changes. Daylight-only values live under `--dl-*`; the named Tailwind utilities
`bg-sand`, `bg-sand-deep`, `bg-chalk`, `text-daylight`, `bg-veil`, `border-seam` are registered in
`app.css` `@theme inline` (they only carry values inside the scope).

**Derived palette (5S).** The whole neutral ramp derives from ONE base — the topbar colour (core
`--bg`): `#EDEFF3` light, `#0D0F13` dark. Every other neutral is a `color-mix(in oklch, …)` of that
base, so changing `--dl-base` re-grounds the entire feed. The topbar and the feed ground are the
SAME colour by design — the bar dissolves into the page; frames float above on `--dl-surface`
lifted by the blue shadow, never by a border. `sand` / `sand-deep` / `chalk` are retired.

| Token | Formula (light) | Computed light | Computed dark |
|---|---|---|---|
| `--dl-ground` | `var(--dl-base)` | `#EDEFF3` | `#0D0F13` |
| `--dl-ground-sunk` | base + `#14110B` 4% | `#E3E5E9` | `#040406` |
| `--dl-surface` | base + `#FFFFFF` 55% | `#F6F8F7` | `#171A1E` |
| `--dl-surface-sunk` | base + `#14110B` 2% | `#E8EAEE` | `#08080C` |
| `--dl-seam` | base + `#14110B` 9% | `#D7D8DC` | `#23262A` |
| `--dl-ink` | base + `#14110B` 92% | `#231F19` | `#F0E5D9` |
| `--dl-ink-soft` | base + `#14110B` 64% | `#5D5555` | `#9F9192` |
| `--dl-ink-faint` | base + `#14110B` **56%** | `#6E6668` | `#8F8385` |
| `--dl-daylight` | fixed | `#3D4FD6` | `#7B8AFF` |
| `--dl-veil` | fixed | `#C9D0F5` | `#2A2F5C` |
| `--dl-shadow` | fixed | `rgba(61,79,214,.10)` | `rgba(0,0,8,.55)` |

(Dark mixes toward `#000`/`#FFF`/`#FFF6E8` — see `daylight.css`; the warm inks keep the Daylight
character on the cooler base.) Static hex fallbacks ship in an `@supports not (color-mix in oklch)`
block.

**Contrast is a build gate, not a review note:** `php artisan feed:check-contrast` recomputes the
formulas (sRGB→OKLab→LCH, shorter hue arc) and fails below ink 7:1 / ink-soft 4.5:1 /
ink-faint 4.5:1 / daylight 4.5:1 on both grounds, both themes — it runs in the Pest suite.
`ink-faint` was tuned to **56%** (drafted 48%/44%) to clear its gate. Editing the palette means
editing `daylight.css` and the command's formula table **together**. The accent stays scarce: the
glint, inline links/mentions, focus rings and the light bar ONLY — never buttons, chip backgrounds
or filled surfaces. Focus is a 2px `--dl-daylight` ring at 2px offset.

## Type

| Role | Latin | Arabic | Class |
|---|---|---|---|
| Display | **Fraunces** variable, `SOFT` + `WONK` axes on | **Amiri** (via the dir-aware `--dl-font-display`) | `.t-note` (24) / `.t-display` (34) |
| Body / UI | **Instrument Sans** | **IBM Plex Sans Arabic** | `.t-body` (15) / `.t-comment` (17) |
| Utility | **Geist Mono** (fallback JetBrains Mono) | same | `.t-meta` (12, uppercase, `.08em`, lining+tabular numerals) |

Latin numerals in both locales for all counters/timestamps (`font-variant-numeric: lining-nums`).
Fonts load from `resources/views/feed/partials/daylight-head.blade.php` on feed pages only
(Google Fonts `display=swap`, per the app-wide pattern). **Deploy note:** the playbook prefers
self-hosting — swapping the partial's `<link>` for `@font-face` rules over locally-stored woff2
subsets is the only change needed.

## Signature classes (`daylight.css`)

- **`.frame`** — a post: chalk surface, 6px radius, one corner **notched 14px at 45° on the
  inline-end top corner** (clip-path; mirrored under RTL). `clip-path` clips `box-shadow`, so the
  blue shadow lives on the **`.frame-shadow`** wrapper via `filter: drop-shadow` — which follows
  the notched silhouette exactly. Compose: `<div class="frame-shadow"><div class="frame">…`.
  `--raised` variant for hover elevation.
- **`.glint`** — the four-point ✦ like control; `.is-lit` pops the mark 300ms and
  `.frame-shadow.is-sweeping` sweeps the shadow ~8px (the light source moves).
- **`.credits`** — the mono editorial credit line (`PHOTOGRAPHY — @rana / STYLING — @hossam`).
- **`.feed-chip`** — inline mono pill, seam hairline; provider colour appears ONLY as a hover
  glint via `--chip-glint`. `.feed-mention` — daylight-coloured mention anchor.
- **`.grain`** — fixed-viewport SVG feTurbulence at 2.5% opacity.
- **`.light-bar`** — 2px scroll-depth gradient (JS sets `--scroll-progress` 0–1).
- **`.skeleton`** — notched loading frames; the light sweep runs on the inline axis so it
  follows the locale direction. **`.well`** — recessed inputs. **`.seam`** — hairline rule.

## Stacking + chrome (round 3, 5AB)

One z-index scale in `app.css` `:root` — **no raw z-indexes anywhere in the feed**:
`--z-base` 0 · `--z-frame-raised` 1 · `--z-rail` 10 · `--z-sticky` 20 · `--z-topbar` 100 ·
`--z-fab` 200 · `--z-popover` 300 · `--z-modal-backdrop` 400 · `--z-modal` 410 ·
`--z-autocomplete` 420 · `--z-toast` 500; `--topbar-h` = 4rem (body padding + scroll-padding
derive from it). The public topbar (`.site-topbar`) is fixed and fully opaque on `--bg`; a
hairline seam fades in past 8px of scroll; under 1100px it hides on scroll-down and returns on
scroll-up (`topbar.js`). Active nav = `aria-current="page"` + the `.nav-underline` gestural SVG
stroke — never the underline alone.

## Round-3 control classes (5AD–5AH)

- **`.action-bar` / `.action-btn`** — the post action row: 40px hit targets, 18px icons,
  hover wash via `--dl-hover*` tokens, `.is-lit` for the lit like, `.action-btn--sm` text
  buttons, `.action-end` pushes SAVE + overflow to the inline-end.
- **`.share-popover`** — the share menu on `--z-popover`.
- **`.media-grid`** (+`--single`/`--two`/`--half`, `.media-tile--lead`, `.media-more`) — the
  post media geometry; single images clamp via container query (56.25cqw–125cqw); `.lightbox*`
  classes style the teleported viewer.
- **Comment typography** — `.comment-author` (14px, normal case), `.comment-handle` /
  `.comment-time` (mono 11px), `.comment-badge` (9px, never shrinks), `.comment-avatar`
  (32px; 24px in replies).
- **`.mention-pop`** — the mention autocomplete, **portalled to `<body>`** on
  `--z-autocomplete` with fixed positioning from the field rect (bottom sheet under 640px).
  It must never live inside a `.frame` (overflow: hidden clips it).
- **`.avatar-fallback`** — display-face initial on a deterministic username-hash tint
  (`color-mix` of surface-sunk + veil).
- **`.rail-reason`** — clamps to two lines (`-webkit-line-clamp`) so a trailing @handle
  survives whole; reasons are never truncated mid-handle.

## Motion

`--dl-dur-fast` / `--dl-dur` / `--dl-dur-slow` = 200/260/320ms on `cubic-bezier(.2,.8,.2,1)`. Everything collapses to
instant changes under `prefers-reduced-motion` (blanket override in `daylight.css`). Logical
properties throughout — RTL mirrors with **zero** directional overrides outside the two notch/sweep
mirrors.

## Motion layer (5Y)

Applied **by JS only** — with JavaScript disabled nothing is hidden and the page reads fully;
`prefers-reduced-motion` skips the whole layer (the blanket collapse also stands). Entrances are
**transform/opacity only**: frames rise 12px + fade 280ms, staggered 40ms capped at 6,
direction-aware (down = from below + .985 scale, up = from above; leaving reverses at 60%),
first-paint frames never animate (LCP is sacred), `will-change` set on start and removed on end.
The **light response** (a frame passing the middle 40% band): the lifted shadow is a
**pre-rendered notched `::after` crossfaded by opacity** — never an animated box-shadow — and the
surface brightens 2% via an overlay veil; both driven by one IntersectionObserver
(`rootMargin: -30% 0px`), zero scroll-loop `getBoundingClientRect`. Offscreen frames use
`content-visibility: auto` + `contain-intrinsic-size`. Chrome: the sticky filter row condenses on
scroll-down (eyebrow hides, padding halves, chips go single-line scrollable) and the composer
trigger collapses to a pill; both expand on scroll-up. Optimistic inserts expand-and-fade
(`.dl-insert`). The like sweep composes with the light response (the sweep animates the base
filter; the lifted layer rides above it). **Manual QA:** profile a 50-frame scroll at 60fps in the
browser performance panel (with the RTL/dark pass).

## Review surface

`GET /design/feed-tokens` renders every token, type role, frame variant and interactive state in
**both themes × both directions** on one page.
