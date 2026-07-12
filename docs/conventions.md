# Conventions

> How we write Fama code. Kept in sync with the code; update in place.

## Controllers & services
- Controllers are **thin**: validate via a Form Request, build a DTO, call a service, return a
  `Response`. No queries or business logic in controllers.
- Business logic lives in `App\Services\*` (extend `App\Services\Service`). Multi-write operations run
  through `runInTransaction()` so they're atomic and get failure logging for free.
- Discrete operations are `App\Actions\*` invokable classes implementing
  `App\Actions\Contracts\Action`; services orchestrate them.

## The JSON envelope (web Ajax + API)
Every controller action returns the same shape:

```json
{ "success": true, "data": {}, "message": null, "errors": null, "meta": null }
```

Use the macros (registered in `AppServiceProvider`):
- `response()->success($data, $message, $meta, $status)`
- `response()->error($message, $errors, $status, $data, $meta)`
- `response()->paginated($paginator, ...)` — items become `data`, pagination goes to `meta.pagination`.

Validation/authentication exceptions are rendered into this envelope automatically for JSON/Ajax
requests (`bootstrap/app.php`). `resources/js/http.js` parses it and throws `ApiError` with the field
error bag.

## Failure-logging convention
Catch → log to the **dedicated channel** with context → rethrow (or return an error envelope):

```php
try {
    return DB::transaction(fn () => /* ... */);
} catch (\Throwable $e) {
    Log::channel('deals')->error($e->getMessage(), ['deal_id' => $id, 'exception' => $e]);
    throw $e;
}
```

`Service::runInTransaction($cb, $context, $channel)` encapsulates exactly this. Channels: `app`
(default), `auth`, `deals`, `media`.

## Transactions
Any operation that writes to more than one table/row is wrapped in a transaction (via
`Service::runInTransaction()` or `DB::transaction()`).

## Models
- `preventLazyLoading` and `preventSilentlyDiscardingAttributes` are on in non-production — **eager
  load** relations (`with(...)`) and only mass-assign declared attributes.
- Lists are **always eager-loaded and always paginated**.

## Media (spatie/laravel-medialibrary)
- All **uploaded** files go through media collections + conversions. Thumbnails are **conversions**,
  never manual `*_url`/`thumbnail_url` columns; those columns (for uploads) become accessors that
  resolve from the media library.
- **External** links/embeds (YouTube/Vimeo, social, brand-collab, press) stay as plain URL columns.
- See `docs/decisions.md` and `docs/specs/schema-master.md` (implementation note).

**Media collections (Phase 1A).** Each `HasMedia` model registers single-file collections + a `thumb`
conversion; the dropped `*_url` columns are replaced by accessors that resolve from these collections
(null when empty):

| Model | Collection(s) → accessor |
|---|---|
| `Talent` | `avatar` → `avatar_url` *(the `hero`/cover collection + `hero_image_url` were removed with the IG-style header — ADR-O)* |
| `PortfolioItem` | `gallery` → `media_url` / `thumbnail_url` (embed items use `embed_url`) |
| `Digital` | `digital` → `media_url` / `thumbnail_url` |
| `BrandCollab` | `logo` → `brand_logo_url` |
| `Review` | `avatar` → `reviewer_avatar_url` |
| `Showreel` | `thumbnail` → `thumbnail_url` (video stays `video_url`) |
| `Project` | `cover` → `cover_image_url` |
| `SoftwareStack` | `icon` → `icon_url` |

Accessors call `loadMissing('media')` so they are safe under `preventLazyLoading`; controllers should
still eager-load `media` on lists.

## Translatable attributes (spatie/laravel-translatable)
Fama is bilingual (en/ar). Policy:
- **Translate** free-text, human-facing copy that a user would reasonably localise: e.g. `headline`,
  `bio`, block `title`, project `title`/`summary`/`body`, campaign
  `title`/`description`, deal-flow step `name`/`instructions`.
- **Do NOT translate:** identifiers, slugs, enums, emails, numbers, dates, URLs, foreign keys, or
  machine keys (`block_types.key`, `deal_flow_steps.key`).
- Storage: translatable columns are `JSON`, declared via `use HasTranslations;` +
  `public array $translatable = ['headline', 'bio', ...];`. Read/write resolve to the active locale;
  use `->getTranslation($attr, $locale)` / `->setTranslation(...)` for explicit locales.
- Validation: validate the submitted locale's value; keep the other locale untouched on partial edits.
- Fallback: missing translations fall back to `APP_FALLBACK_LOCALE` (en).

**Current translatable attributes (Phase 1A — talent side).** These columns are JSON per-locale:

| Model | Translatable attributes |
|---|---|
| `Talent` | `headline`, `bio` |
| `TalentType` | `name`, `description` |
| `BlockType` | `name`, `description` |
| `ProfileBlock` | `title` |
| `Project` | `title`, `role`, `summary`, `body` |
| `LookType` | `name` |
| `Showreel` | `title` |
| `BrandCollab` | `project_title` |
| `PortfolioItem` | `caption` |
| `Equipment` | `notes` |

**Deliberately NOT translatable:** identifiers/slugs/enums/keys; proper nouns (`brand_name`,
`client_name`, `software_name`, equipment `brand`/`model`/`name`); `Review.body` (external text kept
in its original language); the Pricing rate (`rate_unit`/`rate_amount`/`rate_currency`) — numbers/enum/
ISO code, ADR-N.

**UI relabels (copy only, columns unchanged):** the public `slug` is shown as **"Username"** in the
talent UI + validation messages (`UpdateCoreProfileRequest::attributes()`); "Professions" is shown as
**"Skills"** everywhere (the `talent_types` table is unchanged — ADR-N).

**Skill naming — disciplines, not people (ADR-S):** the six seeded skills are named for the
**discipline/activity** — Modeling, Photography, Cinematography, Creative Direction, Styling, Graphic
Design (slugs `modeling` / `photography` / `cinematography` / `creative-direction` / `styling` /
`graphic-design`). The `category` **enum stays** `model | crew | creative` (it gates blocks) — only its
**display labels** are Modeling / Crew / Creative, and a single-chip category group whose label would
duplicate its lone chip (Modeling) **suppresses the redundant header**. Renaming kept the `talent_types`
IDs, so every FK is intact; old `?skill=` deep links break (accepted pre-launch, no redirects).

## RTL / i18n in views
- Set direction from the locale (`<html dir>` in the layouts). Prefer **logical** Tailwind utilities
  (`ms-*`, `me-*`, `ps-*`, `pe-*`, `text-start`, `text-end`) over `left/right` so Arabic mirrors
  automatically. Use `rtl:` / `ltr:` variants only for genuinely direction-specific tweaks.
- Wrap user-facing strings in `__()` / translation files; never hardcode copy in Blade.
- Link to other locales with the `x-language-switcher` component.

## Naming
- Tables/columns: snake_case, plural tables (per `docs/specs/schema-master.md`).
- Classes: `SomethingService`, `SomethingAction`, `SomethingData`, `SomethingPolicy`,
  `SomethingResource`.
- Routes: dashed URLs, dotted names, guard-prefixed (`admin.`, `brand.`, `talent.`).

## Testing (Pest)
- Feature tests for every route/flow; unit tests for services/actions/support.
- Assert the envelope shape for JSON endpoints. Tests run on **MySQL** against a dedicated `fama_test`
  database (`phpunit.xml`) with `RefreshDatabase`, so they exercise the same engine as dev/prod and
  never touch the dev `fama` data. Create it once: `mysql -u root -e "CREATE DATABASE IF NOT EXISTS fama_test"`.
- Definition of Done: tests green, fail-logs + transactions verified, docs updated, no git.

## QA checklist — talent slice (manual)

Run against the seeded demo (`php artisan migrate:fresh --seed`; talent `demo.talent@fama.test`,
slug `demo-talent`). Every dashboard interaction is Ajax — **no full page reload** should occur except
logout. Toggle theme + locale on each page and confirm both render.

**Public pages** (no login)
- [ ] `/{slug}` profile — **Instagram-style header (ADR-O), no cover image**: circular avatar
      (initials fallback), display_name + **@username**, primary skill / headline secondary line,
      stats row **Projects · Views · Rating** (Rating hidden when no approved reviews), bio, optional
      external link, and the **Pricing rate** chip ("From EGP 5,000 / day", hidden when
      unset). **The header no longer shows skill chips** (the tab bar is the navigation). Message +
      Leave-a-review CTAs work. **Two regions (ADR-R):** identity + universal/meta + the **universal
      profile-level blocks**, then **skill tabs**.
- [ ] `/{slug}` **skill tab bar** — reads as **primary navigation**: a **sticky** (under the site header),
      horizontally-scrollable **pill/segmented** bar separated from the identity region by a divider, each
      tab showing the skill **icon**, name, and a **count badge**. The **active** tab is **filled** (accent
      + contrasting label + weight), inactive tabs legible with hover + **focus-visible ring**. Primary tab
      **active by default**; one tab per skill **with visible blocks** (block-less skill → no tab; single
      skill → **no tab bar**). Clicking a tab **lazy-loads** that skill's blocks (no reload, with a
      reduced-motion-aware fade) and updates the URL (`?skill=slug`, shareable + back-button); the panel
      shows the **active skill's name as a heading** and only that skill's blocks (a **different gallery per
      tab** + only that skill's **Projects**). `view_count` bumps once (not per tab switch). **Keyboard:**
      `Tab` reaches the active tab, **arrow / Home / End** move between tabs (RTL-aware, activation follows
      focus). **On mobile** the tabs scroll horizontally with **edge fades** and never wrap. **Scroll down,
      then confirm the bar stays pinned under the header.** Verify **dark + light + RTL**. (No availability
      badge — ADR-L.)
- [ ] `/{slug}/work/{project}` — one project expands (cover, client/role/year, summary, results, body).
      A foreign/unpublished project 404s.
- [ ] `/{slug}/review` — submit writes a **pending** review (shows in the talent's moderation queue).
- [ ] `/{slug}/enquire` — submit lands in `deal_enquiries` (always allowed — no availability gate).
- [ ] `/discover` — **skills-first**: the primary **Skills** control is a **sticky bar** (sticks under the
      header while results scroll) with a **Skills** heading, a **selected-count** badge, and an **"All"**
      reset chip **beside** the groups (**"All" is a neutral reset — NOT a default selection**: no filled state,
      disabled while nothing is chosen). Skills are **multi-select chips grouped by scope** (Modeling / Crew /
      Creative — the groups sit **side by side** as divider-separated columns on one line), each with
      its **icon** and real states — hover, **focus-visible ring**, and a filled-accent **selected** state with a
      **check** (`aria-pressed` toggle buttons in a labelled `role="group"`, keyboard-operable). A **result count**
      ("N talents") shows above the grid; an **active-filter summary row** lists removable chips ("Modeling ×",
      "Cairo ×") with **"Clear all"**. Selecting/removing narrows results (Ajax, no reload) with **skeleton loaders**;
      an **empty state** with "Clear filters" shows when nothing matches. Active filters **sync to the URL** — a
      filtered view is **shareable** and the **back button restores** it; **pagination holds** the filters. The
      demoted **secondary search** (`q`, by name) still filters live.
- [ ] `/discover` **Advanced filters modal** — a **wide** dialog (`sm:max-w-3xl`) with a title + subtitle, a
      **Skills** section, a divider, a **Location** section, and a **Skill-specific** section (scoped selects in a
      2-col grid) that shows a filter **only once its skill is selected** — **crew → Equipment**, **creative →
      Software**, **modeling → Looks**; with **no skill selected** it shows a hint ("Select a skill to reveal its
      filters."), and picking a skill reveals the filter that narrows it. The
      **"Advanced filters"** button (with an active-filter count)
      opens a dialog **teleported to `<body>`** that **always opens centred in the viewport** — **scroll down first,
      then open, and confirm it appears in front of you (not at the page top)**. Body scroll is **locked** (position
      preserved on close). Closes on **×, backdrop click, and ESC**; **focus is trapped** and returns to the trigger.
      The modal is a **staging** area: changes edit a **draft** and
      **nothing applies to the results until "Apply filters"** — verify that toggling a skill / typing a city in
      the modal does **not** change the grid until Apply; ×/backdrop/ESC **discards**; "Clear filters" resets the
      draft without applying. On small screens it's a **bottom sheet** whose **body scrolls**
      (not the page). Verify **dark/light + RTL** (modal, scrim, and bottom sheet mirror) and
      **prefers-reduced-motion**. (No availability filter — ADR-L.)

**Talent dashboard** (`auth:talent`) — sidebar is **Home · Profile · Content · Reviews · Deals**
- [ ] Home — status (draft/live), views, pending-reviews count, **active deals with whose-turn**
      (awaiting_talent highlighted), quick links.
- [ ] Profile editor — the single profile surface:
      - Publish/unpublish toggle (moved from Account); publishing a no-display-name profile → 422.
      - **Profile image (avatar)** — Upload photo / Change photo / Remove (Ajax, no reload): the preview
        updates in place and falls back to initials when removed; a non-image or >5 MB file → 422 inline.
        (No hero/cover uploader — ADR-O; only the circular avatar.)
      - Core fields inline save; **Username** field (the `slug`); a taken username → "username" 422.
      - **Skills** section — add/remove/reorder, set primary; duplicate → 422; adding a skill seeds new blocks.
      - **Pricing rate** — set unit/amount/currency (all-or-nothing; partial → 422; blank clears); currency upper-cased.
      - Blocks — **organised by scope (ADR-Q)**: a Universal / profile-level section + one section per
        skill (primary first). Per scope: add from the scope-eligible picker, drag to reorder (persists,
        scoped), toggle visibility, remove, and **move** a block to another scope (only eligible targets
        shown). Adding a skill creates its tab + seeds its blocks; **removing a skill** confirms first,
        then deletes that tab's blocks while preserving content. Ineligible add/move → 422. (No hero/cover
        uploader — ADR-O.)
      - Projects editor — each project has a **Skill** selector (defaults to the primary skill).
- [ ] Content editors (gallery/projects/digitals/…) — upload image (appears in grid), add via fields,
      reorder, remove. Switch content types via the tabs.
- [ ] Reviews — approve/reject in the moderation queue; filter by status.
- [ ] No standalone **Professions** or **Account** tabs remain (folded into Profile).

**Deals**
- [ ] Inbox — deals listed with status + current step; `awaiting_talent` highlighted; status filter works;
      paginated.
- [ ] Deal room — **timeline-first layout**: header on top (reference/title/counterparty/status/amount +
      "← All deals"); the **message timeline is the central, wide column** (messages + system_events
      interleaved, newest at bottom) with the composer; the **side panel** (narrower, right) holds the
      **current-step action panel** at the top then the **Phases stepper** below it. The action panel
      matches the current `step_type` (form/approval/upload/payment/contract/schedule/message/info) and is
      read-only when it's not the talent's turn. Sending a message and acting on a step both update the
      timeline + stepper via **Ajax with no reload**. On narrow screens the side panel stacks under the
      timeline. Verify **dark/light + RTL** (the whole layout mirrors).
- [ ] The seeded deals cover three states: **awaiting_talent** (quote), **awaiting_brand** (approval),
      **completed** (full loop).

**Cross-cutting**
- [ ] Dark ⇄ light toggle persists across pages; RTL (`/ar`) mirrors layout and shows Arabic strings;
      EN switch returns to the default locale.
- [ ] Acting on another talent's resource → 403; acting out of turn on a deal → 422.
- [ ] No `LazyLoadingViolationException` on any page (preventLazyLoading is on outside production);
      every list is paginated + eager-loaded.
