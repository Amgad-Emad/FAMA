# Changelog

Notable changes to the Fama project. Newest first.

## 2026-07-12 — Talent profile image (avatar) uploader

- **Added the missing profile-image uploader** to the Profile editor's Identity/Core-details section. A
  talent can now **Upload / Change / Remove** their avatar — Ajax, no reload: the preview updates in place
  and falls back to the initials avatar when removed. The image goes to the existing `avatar` single-file
  media collection (ADR-O — only the circular avatar; no cover/hero).
- **Endpoints:** `POST /talent/profile/avatar` (update) and `DELETE /talent/profile/avatar` (remove), both
  returning `{ avatar_url }`. Thin controller (`ProfileEditorController::updateAvatar/removeAvatar`) →
  `TalentProfileService::updateAvatar/removeAvatar` (media ops, fail-logged to the `media` channel).
  Validation via `UpdateAvatarRequest` (`image|mimes:jpg,jpeg,png,webp|max:5120`).
- **Front-end:** `profileEditor` (dashboard.js) gains `avatarUrl` + `uploadAvatar()`/`removeAvatar()` and an
  `avatarInitials` getter; a reactive avatar preview + hidden file input in the editor blade.
- **Tests:** `ProfileEditorTest` gains 7 cases — the uploader renders; upload returns a URL + adds media;
  re-upload replaces (single-file); remove clears it; non-image / >5 MB → 422; guests are redirected.
  **Full Pest suite green (199).** Verified in-browser (uploader renders with the seeded avatar; Remove →
  initials reactively, no reload; DELETE endpoint works) — console clean. Docs updated. No git.

## 2026-07-12 — Fix: MariaDB deploy failure on the Looks functional-index migration

- **Migration `2026_07_11_000100_add_look_types_name_index` failed on MariaDB** (production) with a
  `1064` syntax error: MariaDB has **no functional/expression indexes**, and the old
  `getDriverName() !== 'mysql'` guard didn't catch it because **Laravel reports MariaDB's PDO driver as
  `mysql`**. The migration now inspects `VERSION()` and creates the functional index **only on genuine
  MySQL 8.0.13+**, **skipping it on MariaDB / older MySQL** (the model-scope **Looks** filter still works
  unindexed — `look_types` is a tiny lookup table). `down()` drops the index only if it exists. No change on
  MySQL 8 (dev/CI): the index is still created; **full Pest suite green (193)**. docs/schema.md updated. No git.

## 2026-07-12 — Discovery: scoped filters appear only after a skill is selected

- **Scoped filters are now skill-gated.** In the Advanced-filters modal, a skill-specific filter shows **only
  once its related skill is selected** (crew → Equipment, creative → Software, modeling → Looks) — previously
  they all showed when nothing was selected. With **no skill selected** the Skill-specific section shows a hint
  ("Select a skill to reveal its filters."); picking a skill reveals the filter that narrows it further. The JS
  getters dropped the `|| draft.type.length === 0` clause (`showEquipment = draftScopes.has('crew')`, etc.), and
  a new `hasScopedFilters` getter gates the grid-vs-hint. **Full Pest suite green (193).** Verified in-browser
  (no skill → hint; Photography → Equipment only) — console clean. No git.

## 2026-07-12 — Discovery: scoped filters restored to the modal (shown by selected skill)

- **Reverted the "hide scoped filters" change.** The **Skill-specific** section (Equipment / Software / Looks)
  is back in the (still `max-w-3xl`) Advanced-filters modal, **shown based on the selected skills** — with no
  skill selected all three show; a crew skill reveals **Equipment**, a creative skill **Software**, modeling
  **Looks**. The JS visibility getters (`showEquipment/Software/Looks`, `draftScopes`) were already intact, so
  only the modal markup + subtitle + the two translation strings + `DiscoveryTest` were restored. **Full Pest
  suite green (193).** Verified in-browser (no-skill → all groups; Modeling → Looks only) — console clean. No git.

## 2026-07-12 — Discovery: wider Advanced-filters modal + scoped filters hidden

- **Wider modal.** The Advanced-filters dialog grew from `max-w-2xl` to **`max-w-3xl`**.
- **Skill-specific scoped filters hidden.** The Equipment / Software / Looks group (the filters that show
  based on the selected skill's category) is **removed from the modal UI** — the modal now holds only a
  **Skills** section and a **Location** section (subtitle updated to "Refine by skill and location."). The
  scoped filters remain valid `TalentSearch`/URL filters and stay covered by the backend scoping test; the
  `showEquipment/draftScopes` JS getters remain but are now UI-unused. `DiscoveryTest`'s modal-groups test now
  asserts the modal shows Skills + Location and **does not** render the scoped selects. **Full Pest suite green
  (193).** Verified dark + RTL; console clean. No git.

## 2026-07-12 — Discovery: bigger, polished Advanced-filters modal + "All" no longer a default

- **"All" is a neutral reset, not a default selection.** The "All" chip no longer shows a filled/selected
  state and is **disabled while nothing is chosen** — an unfiltered view highlights no chip (in both the
  sticky bar and the modal, via the shared partial).
- **Bigger, enhanced modal.** The Advanced-filters dialog is now a **large** card (`sm:max-w-2xl`,
  `rounded-2xl`) with a **title + subtitle**, a Skills section (with its own selected-count badge), a
  divider, a **Location** section, and a **Skill-specific** section whose scoped `<select>`s sit in a
  **2-column grid** (using the extra width). Roomier padding, larger rounded inputs/selects, and a larger
  "Apply filters" button. Verified dark/light + RTL; console clean; **full Pest suite green (193)**. No git.

## 2026-07-12 — Discovery: skills groups side-by-side, skills-in-modal, staged modal

- **Scope groups side by side, on one line.** The primary Skills filter lays its scope groups (Modeling /
  Crew / Creative) out **horizontally** as divider-separated **columns** (was a vertical stack), and the
  sticky bar now keeps them all on **one line** (`nowrap` + `overflow-x-auto`, hidden scrollbar) so they sit
  beside each other. The **"All"** reset (renamed from "All skills") sits **beside** the groups, not above.
  The single-chip **Modeling** group keeps an **invisible** label placeholder so its chip stays aligned.
  RTL-mirrored (logical `border-s`). Chips extracted to a shared partial
  `public/partials/skill-filter-chips` (`$nowrap`, `$staged` flags).
- **Skills selector inside the Advanced-filters modal.** The modal now includes the same grouped skill chips
  at the top, so a visitor can pick skills there and the scoped groups below react. With **no skill selected**
  ("All"), **every** scoped group shows (Equipment · Crew, Software · Creative, Looks · Modeling); once skills
  are selected only the groups **matching those categories** remain. The obsolete "Choose … skills for …"
  hint was removed.
- **Modal is now a staging area.** The sticky bar still applies **live**, but the modal edits a **draft**
  snapshot (`draft.*`) and **nothing commits to the results until "Apply filters"**: `applyFilters()` copies
  the draft into `filters` then searches; ×/backdrop/ESC **discards** the draft; **"Clear filters"** resets
  the draft in place without applying. The trigger's filter-count badge reflects the **applied** filters, not
  the unsaved draft. `pruneScopedFilters` (live) was made self-contained on `selectedScopes` so it no longer
  depends on the now-draft-based `showEquipment/Software/Looks` getters.
- **Docs/tests:** talent-spec + conventions + CLAUDE.md updated; `DiscoveryTest`'s scoped-groups test asserts
  the by-scope groups. **Full Pest suite green (193).** Verified in-browser: side-by-side one-line groups, "All"
  beside the groups, skills-in-modal narrowing the scoped groups, and staging (toggling a skill / typing a city
  in the modal leaves the grid unchanged until Apply; discard on close) — dark + RTL, console clean. No git.

## 2026-07-12 — Public profile: skill tabs elevated to primary navigation (presentation-only)

- **Prominent, sticky tab bar.** The skill tabs are now the profile's main navigation: a **sticky** (under
  the site header, `top-16`), horizontally-scrollable **pill / segmented** control, separated from the
  identity region by a divider. Each tab shows the skill's **`icon`** (new self-contained `<x-skill-icon>`
  component, mirroring the discovery chips), its **name**, and a **count badge** (visible blocks in that
  skill).
- **Unmistakable active state.** The active tab is **filled** (`bg-accent` + `text-on-accent` +
  `font-semibold` + shadow), not a faint underline; inactive tabs stay legible (surface + border) with
  **hover** and **`focus-visible`** rings.
- **Accessibility.** Proper `role="tablist"` / `role="tab"` / `role="tabpanel"` with `aria-selected`,
  `aria-controls` / `aria-labelledby`, **roving `tabindex`** (only the active tab is tabbable), and
  **arrow / Home / End** keyboard navigation (RTL-aware; activation follows focus). Visible focus ring.
- **Panel.** Renders the **active skill's name as a heading** (context when the bar scrolls out on mobile)
  and **fades on switch** (reduced-motion-aware — a forced-reflow opacity swap, no `requestAnimationFrame`
  so it's background-tab-safe). **Mobile:** the tabs scroll horizontally with **snap + edge fades** and
  never wrap.
- **De-duplicated:** the header's **skill chips were removed** (the tab bar is the navigation); the
  **primary-skill line** in the header stays.
- **Prompt-H behaviour preserved:** primary tab active by default; tabs **lazy-load** via Ajax on first
  click and cache; `?skill=` deep-link + back-button sync; single-skill talents show **no tab bar**; skills
  with no visible blocks show **no tab**; `view_count` bumps **once** per profile view (server-side), not
  per switch. No schema/query changes.
- **JS:** `profileTabs` (`resources/js/dashboard.js`) gains `labels`, `onTabKey` (keyboard nav), and
  `swapPanel` (fade); the now-unused `jump()` (header chips) was removed.
- **Docs/tests:** talent-spec (prominent tab bar, chips removed) + conventions (QA checklist) + CLAUDE.md
  updated. `ProfileTabsTest` gains: the accessible tab bar with the primary tab active + panel heading; the
  keyboard-nav / roving-tabindex wiring; and the header-no-longer-renders-chips check; the single-skill test
  now asserts no `role="tablist"`. **Full Pest suite green (193, 625 assertions).** Verified in-browser:
  tab switch updates panel + URL without reload, sticky-under-header, arrow-key nav, filled active state —
  dark / light / RTL. No git.

## 2026-07-12 — Discovery: primary skills filter + teleported viewport-centred modal

- **Modal positioning bug fixed (Part 1):** the "Advanced filters" dialog is now **teleported to `<body>`**
  (`x-teleport`) so no transformed/`overflow` ancestor (the filter card, or `main.animate-fade-in-up`'s
  transform) can trap its `position: fixed`. It **always opens centred in the viewport** regardless of scroll,
  over a token **scrim** (`--scrim`, added to the design tokens). Body scroll is **locked** (position preserved
  and restored on close); it closes on **×, backdrop click, and ESC**; **focus is trapped** and returns to the
  trigger; `role="dialog"` + `aria-modal` + `aria-labelledby`; on small screens it's a **bottom sheet whose body
  scrolls** (not the page). Enter/leave use the motion tokens and honour `prefers-reduced-motion`.
  - **Gotcha recorded:** Alpine's `x-transition` **leave** never completes for an `x-teleport`'d node, leaving a
    `display:flex` overlay that traps clicks. Fix: plain `x-show` for display + enter/leave via `:class` + CSS
    transitions on a **mount → `$nextTick`(activate) → (leave) → unmount** cycle, with **`pointer-events-none`
    while inactive** so the page is never trapped during the fade-out. (`$nextTick`, not `requestAnimationFrame`,
    because rAF is paused in background tabs.)
- **Primary skills filter (Part 2):** the Skills selector is now **the** primary control — a **sticky** bar
  (`top-16`) with a **Skills** heading, **selected-count**, and an **"All skills"** clear affordance. Skills are
  **multi-select chips grouped by scope** (Modeling / Crew / Creative) rendering `talent_types.icon` with real
  **states** (hover, `focus-visible` ring, filled-accent **selected + check**); accessible **`aria-pressed`
  toggle buttons** in a labelled `role="group"`. Below: an **active-filter summary row** of removable chips
  ("Modeling ×", "Cairo ×") + **"Clear all"**, and a live **result count** ("N talents"). Applying filters is
  **Ajax** with **skeleton loaders** and an **empty state** (Clear filters); active filters **sync to the URL**
  (shareable + **back/forward** via `popstate`; discrete changes push, typing replaces, **pagination holds
  filters**). `filter[type]` is **multi-select** (comma-separated slugs). The free-text `q` search stays a small
  **secondary** control.
- **Scoped filters (Part 3):** Location stays always-visible; the bare "select a skill" line is replaced by a
  hint that **names** what each selection reveals ("Choose Crew skills for Equipment · Creative for Software ·
  Modeling for Looks"); selecting skills reveals exactly those scoped groups (crew → Equipment, creative →
  Software, modeling → Looks). Apply / Clear re-run the search.
- **Docs/tests:** talent-spec (discovery) + conventions (QA checklist incl. "modal opens in the viewport when
  scrolled") + CLAUDE.md updated. `DiscoveryTest` gains multi-select-type, pagination-holds-multi-filter, the
  primary-control render (sticky, `aria-pressed`, `role="group"`, summary, count), the teleported-modal a11y
  wiring (`x-teleport`, `role=dialog`, `aria-modal`, focus trap, `var(--scrim)`), and the named scoped-groups
  empty state. **Full Pest suite green (190, 608 assertions).** Verified in-browser: modal centred-when-scrolled,
  scroll-lock, ESC/backdrop close, multi-select + summary removal, URL sync + back-button, scoped groups per
  category — dark/light + RTL. No git.

## 2026-07-11 — Skills named as disciplines, not people (ADR-S)

- **Renamed the six `talent_types` (the Skills catalog)** from person-nouns to disciplines/activities:
  Model → **Modeling** (`modeling`), Photographer → **Photography** (`photography`), Cinematographer (DOP)
  → **Cinematography** (`cinematography`), Creative Director → **Creative Direction** (`creative-direction`),
  Stylist → **Styling** (`styling`), Graphic Designer → **Graphic Design** (`graphic-design`). Both `name`
  (en+ar), `slug`, `icon` (`lucide-<slug>`), and `description` change.
- **Migration `2026_07_11_000300_rename_talent_types_to_disciplines`** renames existing rows in place and
  is a **no-op on a fresh DB** (the table is empty when it runs; `TalentTypeSeeder` seeds the new values),
  so `migrate:fresh --seed` and an in-place `migrate` converge. **IDs are unchanged**, so every FK
  (`talent_talent_type`, `block_type_talent_type`, `campaign_talent_types`,
  `brand_creative_need_talent_type`, `profile_blocks.talent_type_id`, `projects.talent_type_id`) is
  untouched — verified on the dev DB (pivot counts identical before/after; `down()` reverses to the
  person-nouns).
- **`category` enum unchanged** (`model | crew | creative` — it gates blocks and scopes discovery filters);
  only its **display labels** are now Modeling / Crew / Creative (`discover.blade.php` + `dashboard.js`
  `scopeLabels`, plus the editor's per-skill category label). A single-chip category group whose label
  duplicates its lone chip (Modeling) **suppresses the redundant header**.
- **Ripples (all data-driven, auto-updated):** talent-card kicker, public-profile secondary line + skill
  chips, admin Skills catalog, brand creative-needs, API lookup now read the new names. Deep links change:
  `?skill=photographer` → `?skill=photography`; **old links break — accepted pre-launch, no redirects.**
- **Seeders/factories/tests:** `TalentTypeSeeder`, `TalentTypeFactory`, `TalentDemoSeeder`,
  `TalentShowcaseSeeder` and every test referencing an old slug/name literal were updated; nothing
  references `photographer` / `stylist` / etc. as a slug literal. `lang/ar.json` gains `Modeling`.
- **⚠ Arabic copy is a first pass — stakeholders should confirm the AR names** (`عرض الأزياء` /
  `التصوير الفوتوغرافي` / `التصوير السينمائي` / `الإدارة الإبداعية` / `تنسيق الأزياء` / `التصميم الجرافيكي`).
- **Docs:** ADR-S (decisions.md); schema-master + conventions + api.md + README + CLAUDE.md updated.
  `brand-spec.md` needed no change (it references the Skills catalog only abstractly). **Full Pest suite
  green (185, 579 assertions).** No `composer api-docs` (mobile API is Phase 4). No git.

## 2026-07-11 — Public profile: identity/universal region + lazy, deep-linked skill tabs (ADR-R)

- **Two stacked regions** (`talent/profile.blade.php`): **Region 1** (always visible) = the IG identity
  header + universal/meta (location, **Pricing rate**, and **clickable skill chips** that activate a tab)
  + the **profile-level blocks** (`talent_type_id = NULL`, visible, in position order). **Region 2** =
  **skill tabs** — one tab per linked skill **with visible blocks** (ordered primary-first, primary active
  by default); a block-less skill has **no tab**; a **single-skill** talent shows no tab bar (renders
  directly). A tab shows only that skill's visible blocks (via the existing partials; grandfathered types
  still render), and its **Projects** block lists only that skill's projects.
- **Lazy + deep-linked:** the active (primary, or `?skill=`) tab renders **server-side**; other tabs are
  fetched on first click via **`GET /{slug}/tab/{skill}`** (`TalentProfileController@tab` → envelope
  `{ html }`, eager-loaded, no N+1) and **cached client-side** (re-click is instant). The active tab is
  mirrored in the URL (`?skill={slug}`) so it's shareable and the **back button** works (`popstate`).
  `view_count` bumps once per profile view (not per tab switch). New `profileTabs` Alpine component; a
  reusable `talent/partials/skill-blocks` partial renders a tab (used server-side + by the lazy endpoint).
- **API contract:** `App\Http\Resources\PublicProfileResource` defines the Phase-4 public-profile shape —
  `identity` (header + universal/meta + pricing rate + skills), `universal_blocks`, and `skills[]` each
  with `blocks[]` (mirrors the two regions). Documented in `docs/api.md`.
- **Docs/tests:** ADR-R; talent-spec + api.md + conventions (QA checklist) + CLAUDE.md + README updated.
  New `ProfileTabsTest` covers: identity + universal + a tab per skill with the primary active server-side;
  a lazy tab returns ONLY that skill's visible blocks (projects scoped); the Projects link to
  `/{slug}/work/{project}`; single-skill → no tab bar; a block-less skill → no tab (its lazy endpoint
  404s); deep-link `?skill=` opens the right tab; unpublished/foreign/unknown-skill 404; and the resource
  shape. Verified in-browser (lazy load, `?skill=` URL, back button, dark/light + `/ar` RTL with
  locale-aware fetch). **Full Pest suite green (185).** No `composer api-docs` (mobile API Phase 4). No git.

## 2026-07-11 — Skill-scoped blocks & projects — one tab per skill (ADR-Q)

- **Schema (`2026_07_11_000200`):** added nullable `talent_type_id` FKs (nullOnDelete) to `profile_blocks`
  and `projects` — NULL = profile-level / universal (above the tabs), NOT NULL = that skill's tab.
  `profile_blocks.position` is now ordered **within a scope** (index `(talent_id, talent_type_id,
  position)`; `projects.talent_type_id` indexed). The migration **backfills** (projects → primary skill;
  blocks → the single skill their gate matches, else NULL) and reports the counts.
- **Seeding is now per-skill (behavioural change):** adding a skill seeds **that skill's** `default_blocks`
  into its own tab (`SeedBlocksForSkill`, replacing the global-merge `MergeDefaultBlocksForTypes` /
  `SeedProfileBlocks`). Dedupe is scoped to (talent, skill, block_type) — a model-photographer gets its
  own **gallery in both tabs** (each with its own `portfolio_items` via `block_id`). `is_repeatable` now
  means "once **per scope**".
- **`ProfileBlockService` is scope-aware:** the picker is per-scope (`availableBlockTypes($talent,
  ?$scope)` — universal blocks in any tab or the universal section; gated blocks only in an eligible
  skill's tab; per-scope repeatability). `addBlock`/`reorder` take a scope; new **`moveBlock`** re-stamps
  `talent_type_id` (validated). New route `PATCH /talent/profile/blocks/{block}/move`; add/reorder accept
  `talent_type_id`; `ProfileBlockResource` + the block-type catalog expose the scope + gate metadata.
- **Removing a skill preserves content:** `SkillsService::removeType` deletes the skill's tab blocks but
  un-links gallery items (`block_id` → NULL) and un-scopes its projects (`talent_type_id` → NULL); the
  editor confirms first and the removal is logged.
- **Profile editor (tab-aware):** blocks are grouped into a Universal / profile-level section + one
  section per skill (primary first) — per-scope add picker, scoped drag-reorder, move-between-scopes
  (only eligible targets), and a confirm-to-remove skill. The **projects editor** gains a Skill selector
  (defaults to the primary skill).
- **Public profile:** renders the universal section, then **one Alpine tab per skill** (no reload) — each
  tab shows its own blocks + gallery. Gallery/projects partials are now block-/skill-scoped. Demo &
  showcase seeders curate a clean tabbed layout (universal talent-level blocks at profile level, gated +
  gallery blocks per tab).
- **Docs/tests:** ADR-Q; schema-master + talent-spec + schema.md + architecture + conventions + api.md +
  CLAUDE.md updated. Tests cover per-skill seeding (2nd gallery, no cross-skill dedupe), per-scope picker
  eligibility + repeatability, scoped reorder, move-between-scopes (+ validation), skill-removal content
  preservation, projects-carry-a-skill, and the backfill via a clean `migrate:fresh`. Verified in-browser
  (profile tabs switch galleries; editor scope groups + move-eligibility). **Full Pest suite green (175).**
  No mobile-API doc regeneration (Phase 4; no `composer api-docs`). No git.

## 2026-07-11 — Discovery: skills-first with an advanced-filters modal + scoped filters

- **Skills-first UI** (`resources/views/public/discover.blade.php`, `talentSearch` Alpine component): the
  primary filter is **Skills** (the `talent_types` catalog), a prominent selector **grouped by scope**
  (Models / Crew / Creative) from the skills' `category`. Selecting skill(s) filters live (Ajax, envelope,
  paginated, eager-loaded — no reload).
- **"Advanced filters" modal**: a button (with an **active-filter count** badge) opens a modal holding the
  full filter set, **scoped by the selected skills' category** — **Location** (city, country) always,
  **crew → Equipment**, **creative → Software**, **model → Looks**; out-of-scope groups are hidden (and
  their values pruned). **Apply filters** re-runs the paginated search. The free-text `q` search is
  demoted to a small **secondary** control.
- **New `looks` filter (model scope)**: `App\Queries\TalentSearch` gains a whitelisted `filter[looks]`
  matching `look_types.name` on the English path (`name->en`). Because `name` is a translatable **JSON**
  column, a plain index is impossible — migration `2026_07_11_000100` adds a **functional index**
  `look_types_name_en_index` on `CAST(name->>'$.en' AS CHAR(191))` (MySQL-only, guarded by driver).
  Confirmed the removed **availability** filter is gone (ADR-L). Everything stays eager-loaded + paginated
  (no N+1). `country` is now also surfaced in the UI (was already whitelisted).
- **Docs/tests.** talent-spec (skills-first discovery, advanced-filters modal, scoped filters, secondary
  search, Looks filter + a comp-card-ranges future note), `docs/schema.md` (the functional index),
  `docs/conventions.md` (refreshed discovery QA checklist), `docs/api.md` (`looks` filter), CLAUDE.md.
  `DiscoveryTest` gains scoped-filter coverage (crew→equipment, creative→software, **model→looks**),
  country + secondary-`q` filtering, and filtered-pagination; the render test asserts the skills-first +
  advanced-filters entry. Verified in-browser (dark, light, `/ar` RTL — skills groups + modal mirror).
  **Full Pest suite green (171).** No mobile-API doc regeneration (Phase 4; no `composer api-docs`). No git.

## 2026-07-11 — Public profile primary CTA: "Message" (interim brand-auth redirect, ADR-P)

- **CTA swap.** The public profile's primary CTA is now **Message** (was **Contact** — Prompt C/ADR-O);
  "Leave a review" stays as the secondary CTA. Message points at a reserved, talent-scoped named route
  **`brand.talents.message`** (`GET /brand/talents/{talent:slug}/message`,
  `App\Http\Controllers\Brand\TalentMessageController`).
- **Interim behaviour (no chat wiring, no new tables).** The route is public and branches on brand auth:
  a visitor **not** authenticated as a brand is redirected to brand auth (`route('login', ['role' =>
  'brand'])` — the login view now pre-selects the Brand role from `?role`) with this talent's public
  profile stored as `url.intended`; an **authenticated brand** gets a **"Messaging coming soon"** flash
  back on the profile. A clearly-marked `TODO(brand-messaging)` hook marks where the real brand↔talent
  chat (`deal_messages`) / deal initiation attaches later. `talent.enquire` (deal_enquiries) is untouched.
- **Supporting bits.** Added a token-based flash banner to `public-layout` (renders `session('status')`);
  new `Message` / `Messaging coming soon` / `Dismiss` strings in `ar.json`.
- **Docs/tests.** ADR-P added to `docs/decisions.md`; talent-spec (public-profile CTA), CLAUDE.md, and this
  changelog updated. `TalentProfileTest` now asserts the **Message** CTA (and no "Contact") and that it
  links to `brand.talents.message`; new `tests/Feature/Brand/TalentMessageTest` covers the guest→brand-auth
  redirect (return URL preserved), the authed-brand "coming soon" stub, and a 404 for an unknown slug.
  Verified in-browser (Message CTA → `/login?role=brand` with Brand pre-selected). **Full Pest suite green
  (168).** No git.

## 2026-07-11 — Deal room: timeline-first layout (message thread central)

- **Layout-only swap** of the talent deal room (`resources/views/talent/deals/show.blade.php`): the
  **message timeline** is now the primary, central, wide column (conversation view — messages +
  system_events interleaved, newest at the bottom — with the composer), and the **current-step action
  panel** + **phases stepper** moved into the narrower **side panel** (action panel on top, stepper
  below). The header stays on top (reference, title, counterparty, status badge, amount) and now carries
  the "← All deals" link. Added `Phases` / `Current step` section labels (+ `ar.json`).
- **No behaviour change:** the `dealRoom` Alpine component, all endpoints
  (`thread`/`advance`/`reject`/`skip`/`message`), and `DealService` are untouched — sending a message and
  acting on a step still update the timeline + stepper via **Ajax with no reload**. No schema, no
  state-machine, no service changes.
- **Responsive + themed:** the side panel stacks under the timeline on narrow screens; verified in-browser
  in **dark, light, and `/ar` RTL** (the whole layout mirrors correctly).
- **Docs/tests:** talent-spec (deal-room: timeline-primary, phases in the side panel), conventions
  (refreshed deal-room QA checklist), CLAUDE.md. `DealRoomTest` gains a structural render assertion
  (`assertSeeInOrder`: All deals → Timeline → composer → Current step → Phases); message-send + step-action
  tests unchanged and green. **Full Pest suite green (164).** No git. *(Only the talent deal room exists;
  the brand deal room is Phase 2C and should reuse this layout.)*

## 2026-07-10 — Instagram-style public profile header + Pricing rate shown (ADR-O)

- **Cover/hero image removed.** Deprecated the `hero` media collection + `hero_image_url` accessor on
  `Talent`; removed the editor's hero uploader (`ProfileEditorController::uploadHero`, the `profile.hero`
  route, `TalentProfileService::setHeroImage`, and the Alpine hero state in `dashboard.js`). No column
  drop (hero was a media-library accessor). The **`avatar`** collection stays. The `hero` **block type**
  in the catalog/`default_blocks` is a separate concept and is untouched (still skipped in the render
  loop — the header replaces it). Demo/showcase seeders no longer attach a hero cover.
- **Instagram-style header** (`resources/views/talent/profile.blade.php`, token-only, dark + light +
  RTL): a thin brand accent strip; a large **circular avatar** (`avatar` collection; initials/gradient
  fallback — a new `2xl` size on `x-ui.avatar`); **display_name** + **@username** (the `slug`) + an
  optional "…" menu (copy profile link); a secondary line (primary **Skill** or headline); a three-item
  **stats row** — **Projects** (count of `projects`) · **Views** (`view_count`) · **Rating** (avg of
  approved reviews, hidden when none); the **bio**; an optional external-link row; and **Contact** /
  **Leave a review** CTAs. Header stats read from already eager-loaded relations — no N+1.
- **Pricing rate shown** (ADR-N) near the identity as a "From {currency} {amount} / {unit}" chip
  (formatted, e.g. "From EGP 5,000 / day"), hidden entirely when no rate is set.
- **Consistency:** confirmed the header carries no availability badge and no services/affiliations/press
  (ADR-K/L/M); remaining blocks still render in position order.
- **Docs/tests:** talent-spec (IG header, no cover, pricing shown), conventions (media map: `hero`
  removed; refreshed QA checklist), decisions (ADR-O), CLAUDE.md, README, changelog. `TalentProfileTest`
  gains header / @username / stats / pricing / rating-visibility coverage; the `Talent` media-collection
  test drops `hero`. Verified in-browser (dark, light, `/ar` RTL). **Full Pest suite green (163).** No git.

## 2026-07-10 — Profile consolidation, "Professions → Skills" rename & Pricing rate (ADR-N)

- **Skills rename.** "Professions" is now **Skills** across all user-facing copy, Blade, translation keys
  and routes (route segment `professions` → `skills`; names `talent.professions*` →
  `talent.profile.skills*`). Talent-side symbols renamed to match: `ProfessionsService` → `SkillsService`,
  `ProfessionController` → `SkillController`, `StoreProfessionRequest` → `StoreSkillRequest`. The
  `talent_types` table + `TalentType` model + `talent_type_id` FKs are **kept** as the physical layer —
  `talent_types` is the **Skills catalog** (a physical rename would cascade into brands/campaigns; noted
  as a deliberate future migration in `schema-master.md` + ADR-N).
- **Editor consolidation.** The standalone **Professions** and **Account** tabs are folded into the
  **Profile editor**, so the talent sidebar is now **Home · Profile · Content · Reviews · Deals**. The
  Profile editor is the single profile surface: Identity, **Skills** (`SkillController` under
  `/talent/profile/skills*`), **Username** (the public `slug`, relabelled "Username" in UI + validation
  via `UpdateCoreProfileRequest::attributes()`; column unchanged), **Publish**
  (`PATCH /talent/profile/publish`, moved from Account), **Pricing rate**, and Blocks. Removed
  `AccountController` + `UpdateAccountRequest` + the `account`/`professions` routes, views and the
  `professionsManager` Alpine component (folded into `profileEditor`).
- **Pricing rate (replaces the removed rate card).** Migration `2026_07_10_000500` adds `rate_unit`
  ENUM(project, day, hour) / `rate_amount` DECIMAL(10,2) / `rate_currency` CHAR(3) to `talents` (nullable,
  NOT translatable). `PATCH /talent/profile/pricing` via `TalentProfileService::updatePricingRate` +
  `UpdatePricingRateRequest` — **all-or-nothing** (`required_with` makes any one field require the other
  two; a blank amount clears the whole rate; currency upper-cased). Demo talent seeded with a sample rate.
- **Docs.** talent-spec (Skills section + Account/Publish merged + Pricing rate), schema-master
  (`talent_types` = Skills catalog note + `rate_*` on `talents`), `docs/schema.md`, `docs/architecture.md`,
  `docs/api.md` (routes: skills under profile, publish/pricing, Account removed), `docs/conventions.md`
  (rate not translatable, "slug shown as Username", refreshed QA checklist), `docs/decisions.md` (ADR-N),
  CLAUDE.md and README updated in place.
- **Tests.** `ProfessionsServiceTest` → `SkillsServiceTest`, `ProfessionsTest` → `SkillsTest` (routes under
  `/talent/profile/skills*`); publish + username now tested via the profile routes; new pricing-rate CRUD
  + all-or-nothing 422 + username-relabel-message coverage; consolidated-editor render test. No
  "profession" left in user-facing copy or routes. **Full Pest suite green (160).** *(No `composer
  api-docs` run — the mobile API is Phase 4 and no `api-docs` script exists yet; `docs/api.md` updated by
  hand. The admin "Skills" relabel + `ProfessionCatalogService` → `SkillCatalogService` apply when the
  admin side is built — not present in this talent-slice codebase.)*

## 2026-07-10 — Remove three talent features (rate card, availability & travel, affiliations & press)

- **Removed entirely (ADR-K/L/M):** the rate card / **services**, **availability & travel**, and
  **affiliations & press** talent features — every trace across the codebase.
  - **Rate card / services (ADR-K):** dropped the `services` table, `Service` model/factory/state
    machine/policy, the `services` block (catalog row + content editor + public partial + its key in
    every `default_blocks`), and the rate-card routes/controller/request/resource. **Deal-engine ripple:**
    `service_id` dropped from `deals` + `deal_enquiries` (FK + column); `service()` relations,
    `DealResource`/deal payloads, `InitiateDeal`, `ConvertEnquiryToDeal`, `StoreEnquiryRequest`, and the
    enquiry Blade stripped. Deal amount now comes from the flow's form/quote step; the single Pricing rate
    (`booking_type`/`booking_value`) replaces the rate card.
  - **Availability & travel (ADR-L):** dropped `availability_status` (+ its discovery index and the
    `Availability` state machine), the public-profile availability badge, the enquiry availability gate
    (**enquiries are always allowed now**), and `willing_to_travel` / `travel_regions` / `rate_tier`
    (superseded by the Pricing rate). Removed the dashboard route/controller/request/resource, sidebar
    entry, Alpine handler, discovery filter, and dashboard-home badge.
  - **Affiliations & press (ADR-M):** dropped `agency_affiliations` + `press_features` (tables, models,
    factories, the `Affiliation` state machine, both policies), the `press` block (catalog row + content
    editor + public partial + its key in `default_blocks`), the affiliations section, and the
    routes/controllers/requests/resources.
- **Migrations (append-only):** `2026_07_10_0001` drops `deals`/`deal_enquiries` `service_id`; `_0002`
  drops the availability/travel columns (index first); `_0003` drops the three tables; `_0004` removes the
  three block types + their category/type gates + any existing `profile_blocks` rows (no-op on fresh).
- **Seeders:** stripped `services`/`press` from every `default_blocks`, removed the three block-type rows,
  and cleaned the demo/showcase seeders of all availability/travel/rate-card/affiliation references.
- **Docs:** ADR-K/L/M added to `docs/decisions.md`; talent-spec, schema-master, `docs/schema.md`,
  `docs/architecture.md`, `docs/api.md`, `docs/conventions.md` (media map + translatable list + QA
  checklist), CLAUDE.md, and README updated in place.
- **Tests:** removed/adjusted the removed-features' tests; **full Pest suite green (155)**; migrations run
  clean on `fama_test`; `migrate:fresh --seed` completes; app boots; public profile + talent dashboard
  render with the features gone (no dead routes/blocks/badge/imports). No git.

## 2026-07-08 — Talent slice: production-grade sign-off

- **Audit (no defects found):** `preventLazyLoading` + `preventSilentlyDiscardingAttributes` on (suite
  green = no N+1); every list endpoint paginated + eager-loaded; every service multi-write wrapped in
  `runInTransaction` with fail-logging to the right channel (deals → `deals`, hero upload → `media`,
  rest → `app`).
- **Coverage +2:** dashboard active-deals/whose-turn render test; engine auto-completes an automatic
  (auto-confirm payment) step with no human turn. Suite: 168 green.
- **Demo dataset:** the demo talent now carries **three deals at different steps** (awaiting_talent /
  awaiting_brand / completed) alongside full blocks/content/images; 10 showcase talents unchanged.
- **Docs:** manual QA checklist added to `docs/conventions.md`; README gains a Status section; CLAUDE.md
  marks the **talent phase COMPLETE** and points to **Phase 2A**.

## 2026-07-08 — Rename case studies → projects (full)

- Completed the earlier label-only change into a full identifier rename across the codebase: model
  `CaseStudy` → `Project`, table `case_studies` → `projects`, relation `Talent::caseStudies()` →
  `projects()`, block-catalog key `case_studies` → `projects` (+ partial `talent/blocks/projects.blade.php`),
  content-type key + registry, public controller `CaseStudyController` → `ProjectController`, route param
  `{caseStudy}` → `{project}` (URL `/{slug}/work/{project}`), factory, seeders (`default_blocks`,
  `BlockTypeSeeder` key), the public view, and the feature test. Docs (schema/architecture/api/conventions/
  specs) updated to match. Route name `talent.work` and the `/work/` segment kept. Full DB rebuilt +
  re-seeded; suite green.

## 2026-07-07 — Showcase demo data: 10 talents with images

- **`TalentShowcaseSeeder`** — ten published talents spanning all six professions (single- and
  multi-type: model, photographer, cinematographer, creative director, stylist, graphic designer, plus
  combinations), each with curated headline/bio/city/services/reviews and category-appropriate content,
  so discovery and profiles render with varied structures (5–9 blocks, rich vs lean).
- **Real images** — hero, avatar and gallery covers are generated locally with GD (deterministic
  gradient covers, no external assets/CDN) via the shared `Concerns\GeneratesCoverImages` trait, and
  attached through medialibrary. `TalentDemoSeeder` (Layla) now gets images too. Registered in
  `DatabaseSeeder`. Verified in-browser: discovery lists 11 talents with images; profiles show
  hero/avatar/gallery.

## 2026-07-06 — QA pass: discovery filter, dashboard deals, real demo data

- **Discovery profession filter showed `[object Object]`** (fixed): the filter printed the raw
  translations map; now resolves via `t(type.name)`.
- **Dashboard "Active deals" was a stale Phase-1E placeholder** (fixed): `DashboardController` loads the
  talent's live (non-terminal) deals and the home renders them with whose-turn highlighting + links to
  the deal room; empty state links to the inbox.
- **`<title>` doubled the suffix** ("Dashboard — Fama — Fama") — fixed in `talent-layout`.
- **Real demo data:** `TalentDemoSeeder` now seeds curated content (gallery captions, two Projects,
  rate-card services, four named reviews, brand collaborations) instead of Lorem-ipsum, plus a **live
  in-progress deal** (brand submitted the brief → talent's turn to quote) so the deal inbox/room and the
  dashboard demo the full lifecycle. New `ar.json` string for the empty-deals state.

## 2026-07-06 — New theme: cloud / graphite / teal + Bricolage + Sora

- Replaced the design-token palette and font pairing across every page (token-only change in
  `resources/css/app.css` + font `<link>` in `partials/design-head`): cool **cloud** surfaces,
  **graphite** ink, **teal** accent — for both light (`:root`) and dark (`[data-theme="dark"]`).
  (Surfaces were briefly peach, then swapped to cool cloud; only the surface/line tokens differ.)
- Fonts: **Bricolage Grotesque** (display) + **Sora** (UI/body) + **IBM Plex Sans Arabic** + **IBM Plex
  Mono**. The `font-display` utility now resolves through the dir-aware `--font-head`, so RTL/Arabic
  headings use IBM Plex Sans Arabic (Bricolage is Latin-only). Derived tokens (primary/on-primary/
  on-accent) re-tuned to the new palette; radii/shadows/motion unchanged. Verified in light, dark, RTL.

## 2026-07-06 — Content upload fix + "Projects" rename

- **Gallery/content upload broken (fixed):** the drop-zone and add-item form post the blank form which
  includes `position: null`; `position` is a NOT NULL column, so the insert 500'd. `BlockContentController::store`
  now appends (`position ?? count()`) instead of inserting null, and `createAndUpload` (dashboard.js)
  infers `media_type` from the dropped file. Regression test added.
- **"Case studies" → "Projects":** renamed the user-facing label (content-editor tab) and the
  `case_studies` block-type catalog name (en "Projects" / ar "المشاريع") + `lang/ar.json`. Internal
  identifiers (table `case_studies`, model `CaseStudy`, route `talent.work`) are unchanged.

## 2026-07-06 — RTL fixes + Arabic UI translations

- **Sidebar hidden in RTL (fixed):** the talent-layout `<aside>` mixed a static `sm:translate-x-0`
  desktop override with a dynamic `rtl:translate-x-full`; the `rtl:` rule ordered after `sm:` in the
  cascade and shoved the sidebar off-screen on desktop RTL. Scoped the off-canvas transform to
  `max-sm:` (mobile only) + added `start-0`, so desktop always uses the static in-flow column.
- **Couldn't switch back to EN (fixed):** with `hideDefaultLocaleInURL=true`, the `localeSessionRedirect`
  middleware bounced the prefix-less EN URL back to `/ar` (session locale trapped). Removed
  `localeSessionRedirect` from the locale route group — the URL prefix is now the single source of truth.
- **Arabic UI translations:** added `lang/ar.json` (313 strings — the full talent dashboard + public
  pages + auth) and `lang/{ar,en}/auth.php` for the dotted `auth.*` keys. `__()` now renders Arabic on
  `/ar` and English on the default locale.

## 2026-07-06 — Deal engine (Phase 1E, shared infrastructure)

- **Schema:** `deal_flows`, `deal_flow_steps`, `deals`, `deal_steps`, `deal_messages`, `deal_enquiries`
  (+ models, factories, `DealFlowSeeder` "Standard Booking"). A **minimal `brands` stub** table lands
  too (auth + name/slug + `is_complete` gate) so `deals.brand_id` FKs; Phase 1B extends it. `deal_steps`
  snapshots `settings`/`is_required`/`is_skippable` (ADR-4).
- **Engine:** StepHandler Strategy + `StepHandlerFactory` — one handler per step_type
  (form/approval/upload/payment/contract/message/schedule/info); `PaymentStepHandler` manual-vs-auto
  per ADR-B (default **manual**). Actions `SnapshotDealFlowSteps` / `InitiateDeal` / `AdvanceDeal` /
  `RejectStep` (loop-back) / `ConvertEnquiryToDeal`, sharing the `DealProgression` engine (one active
  step, status mirrors actor, auto system steps, system_events). State machines Deal / DealStep /
  DealMessage. `DealService` orchestrates in transactions with `deals`-channel fail-logging.
- **Booking CTA:** public `/{slug}/enquire` → `deal_enquiries` (availability-checked, no login),
  replacing the fire-and-forget enquiry; converts to a deal after brand auth (Phase 2).
- **Talent UI:** deal room (turn-aware stepper + `step_type` action panel + chat/system-event timeline
  + free messaging) and deals inbox (status/current-step/whose-turn, filter, paginate) — Blade shells +
  Alpine (`resources/js/deals.js`) on http.js, JSON envelopes, ownership 403 / out-of-turn 422.
- **Tests green:** 165 passed / 493 assertions (+29) — handlers, deal/step/message transitions (incl.
  illegal, reject-loop, skip), full initiate→advance→complete loop, convert, and the talent deal room
  (render, thread, advance, out-of-turn, message, ownership, inbox filter). docs (architecture engine +
  handler map, schema, api) updated. No git.

## 2026-07-06 — Public pages: profile, case study, reviews, discovery

- **Case-study detail** `GET /{slug}/work/{caseStudy}` (`CaseStudyController` + `public/case-study`
  view) — one `case_studies` record expanded; 404 unless published & owned. Profile case-study cards now
  link here.
- **Public review submission** `GET|POST /{slug}/review` (`PublicReviewController` + `StoreReviewRequest`
  + Alpine form) — writes a pending review (`is_approved = false`); "Leave a review" CTA added to the
  profile header.
- **Discovery / search** (ADR-6 applied, talent side): migration
  `add_discovery_search_indexes` (talents availability/published/city/country, `talent_talent_type.
  talent_type_id`, `equipment.category`, `software_stack.software_name`); `App\Queries\TalentSearch`
  (spatie/laravel-query-builder — filters type/category/availability/city/country/equipment/software/q,
  paginated, eager-loaded); `DiscoveryController` (page + Ajax) + `public/discover` view with the
  `talentSearch` Alpine component; `TalentCardResource`. "Discover" link in the public header.
- **Talent profile** `GET /{slug}` was already live (Phase 1 front-end); confirmed it renders the header
  from `talent_talent_type` (primary leading), visible blocks in position order, bumps `view_count` via
  event, and eager-loads everything (no N+1).
- **Tests green:** 136 passed / 431 assertions (+12) — profile & case-study render (+404s), review
  submission writes pending (+validation/unpublished 404), discovery filters (type/availability/
  location/equipment/software) + pagination. docs/schema.md (indexes) + api.md updated. No git.

## 2026-07-06 — Talent dashboard (talent guard)

- **Full talent dashboard** under `auth:talent` (`routes/talent.php`, `app/Http/Controllers/Talent/*`):
  home (draft/live, view_count, pending reviews, deals slot), profile editor (core fields + reorderable
  blocks + eligibility picker + hero upload), professions manager, block content editors (registry-driven
  controller for gallery/digitals/showreel/equipment/case-studies/software/brand-collabs/looks with
  medialibrary upload), rate card, availability & travel, reviews moderation, affiliations & press, and
  account/settings (slug + publish toggle).
- **Thin controllers → Phase 1B services**; Form Requests (`app/Http/Requests/Talent`) validate,
  Resources (`app/Http/Resources`) shape output, everything returns the JSON envelope. Own-resource
  access enforced (403 on foreign); domain-rule/illegal-transition errors return 422 envelopes
  (`bootstrap/app.php` renders).
- **Front-end**: Blade shells + Alpine (`resources/js/dashboard.js`: `profileEditor`, `professionsManager`,
  `crudList`) on the shared `http.js` wrapper — no page reloads, optimistic drag-reorder, inline
  validation errors, medialibrary uploads with skeleton/loading, dark/light + RTL. New `x-talent-layout`.
- **Tests green:** 124 passed / 400 assertions (+23) — each page's happy path, guest→login, admin
  rejection, ownership 403s, eligibility/duplicate 422s, and a media upload. No git.

## 2026-07-05 — Talent domain logic: block engine, services & state machines

- **Block engine.** Actions `MergeDefaultBlocksForTypes` (merge + de-dupe) and `SeedProfileBlocks`
  (idempotent seeding, Created → Draft). `ProfileBlockService` with an eligibility-aware picker
  (active + universal/by-category/by-type − non-repeatable already present) and add/fill/reorder/
  show-hide/remove; rendering still resolves via `block_type_id` so grandfathered blocks render.
- **Services.** `ProfessionsService` (add/remove types, primary, reorder; seeds missing blocks; blocks
  duplicates) and `TalentProfileService` (core fields, hero, availability, publish/unpublish, rate-card
  CRUD, reviews moderation, affiliations & press). All multi-write ops are `DB::transaction` +
  fail-logged via the service base.
- **State machines** (spatie/laravel-model-states) for 7 lifecycles — talent profile, availability,
  block, review, service, affiliation, portfolio media — with explicit transitions + a guarded `ToLive`
  publish. Added `status` columns (backfilled); the old booleans (`is_published`/`is_visible`/
  `is_approved`/`is_active`/`is_current`) are kept as projections synced by `SyncStateProjections`.
- **Events/listeners** (auto-discovered): `TalentProfileViewed` → view-count; `StateChanged` →
  projection sync + `published_at`; media added → log (media channel); conversion completed → advance
  portfolio media state. Controller now dispatches the view event.
- **Policies:** a talent may only manage its own resources (7 policies via `BasePolicy::owns`).
- **Tests green:** 101 passed / 294 assertions (+32) — every action, service path, and state transition
  incl. illegal ones (`CouldNotPerformTransition`), plus events and policy ownership. No git.

## 2026-07-05 — Design system foundation + live Talent Profile

- **Adopted the Fama design system** (from `public/fama-front`) into the real stack. Ported the design
  tokens (light + `[data-theme='dark']`) into `resources/css/app.css`, mapped them to Tailwind via
  `@theme inline` (theme-aware `bg-surface`/`text-ink`/`bg-accent`/`font-display`/`shadow-e2`…), added
  the Bodoni Moda + IBM Plex Sans/Arabic/Mono fonts, and switched dark mode from the `.dark` class to
  the `data-theme` attribute (layouts, nav toggle, and the `dark:` variant all reconciled).
- **Base UI components** (`resources/views/components/ui/*`): button, card, chip, badge, avatar
  (image/initials), eyebrow, stat, section — token-bound, dark + RTL aware. Plus a public layout,
  `x-theme-toggle`, and a DS locale switcher.
- **Live public Talent Profile** — `GET /{slug}` (`TalentProfileController`) renders a published talent
  (hero, identity header, then `profile_blocks` in order via `talent/blocks/{key}` partials) bound to
  the seeded demo talent. Verified in light, dark, LTR and Arabic/RTL against the running app; missing
  media degrades to gradient/initials placeholders.
- Removed a stray `es` (and duplicate `en`) locale from `config/laravellocalization.php` — supported
  locales are now **en, ar** only.
- **Tests green:** 69 passed / 206 assertions (+4 profile tests: published→200, draft/unknown→404,
  view-count increments). Existing Breeze pages re-themed without breakage. No git.

## 2026-07-05 — Test suite runs on MySQL

- Switched the Pest suite from in-memory SQLite to **MySQL** against a dedicated `fama_test` database
  (`phpunit.xml`: `DB_CONNECTION=mysql`, `DB_DATABASE=fama_test`), so tests exercise the same engine as
  dev/prod and never touch the dev `fama` data. Created `fama_test`. Full suite green (65/200) on MySQL.

## 2026-07-05 — Phase 1A: talent side + block system

- **Migrations** (4 files): `talents` (soft deletes); `talent_types` + `talent_talent_type` pivot; the
  block system (`block_types`, `block_type_category`, `block_type_talent_type`, `profile_blocks`); and
  13 talent content tables (portfolio_items, brand_collabs, reviews, services, comp_cards[1:1],
  look_types, digitals, showreels, equipment, case_studies, software_stack, agency_affiliations,
  press_features).
- **Models** (18): full `Talent` (SoftDeletes + HasMedia + HasTranslations, slug auto-gen), TalentType,
  BlockType, BlockTypeCategory, ProfileBlock, and the 13 content models. Relationships both directions,
  casts, sensible default eager-loads (`ProfileBlock` → `blockType`).
- **Media** (ADR-5): uploaded-asset `*_url`/`thumbnail_url` columns dropped and replaced by media-library
  collections + `thumb` conversions + accessors; external links/embeds kept as plain columns (+ new
  `portfolio_items.embed_url`).
- **Translatable** (ADR-7): content fields stored as per-locale JSON; final list recorded in
  `docs/conventions.md`.
- **Factories** for every model + seeders: `TalentTypeSeeder` (six professions), `BlockTypeSeeder`
  (block catalog with category gates), and `TalentDemoSeeder` — a multi-type (model + photographer)
  talent with merged/deduped blocks and populated content, wired into `DatabaseSeeder`.
- **Notes:** profile fields made nullable for progressive onboarding; `Talent` needs an explicit
  `$table` (the inflector leaves "talent" unpluralized); dev DB is MySQL, tests run on in-memory SQLite.
- **Tests green:** 65 passed / 200 assertions (relationships, casts, media collections, translatable,
  soft deletes, pivot uniqueness, demo seeder). Migrations run clean. No git.

## 2026-07-05 — Decision log formalized

- **`docs/decisions.md` rewritten as a lightweight ADR log** (Context / Decision / Status /
  Consequences). Recorded 10 **Accepted** ADRs — multi-guard auth; Services + Actions with one shared
  JSON envelope; state machines for every lifecycle; configurable deal-flow steps (Strategy/Factory,
  snapshotted into `deal_steps`); media library as source of truth for uploads; promoting
  query-critical JSON to pivots/indexed columns for discovery & search; i18n + RTL; quality gates;
  docs/process discipline; and the retained Tailwind v4 toolchain choice.
- Recorded **OPEN** decisions (needs owner) so they surface every session: A) three brand-side user
  modes (product); B) final-payment-leg automation boundary as a `PaymentStepHandler` setting
  (Kanta/billing); C) talent admission flow — self-signup vs admin approval (product); D) web login UX
  (unified role-aware assumed); E) confirm `brand-spec.md` completeness before Phase 2; plus retained
  F) `deals.campaign_id` FK and G) brand/talent password-reset tables.
- No code changes; no git.

## 2026-07-01 — Phase 0: foundation

- **App & packages.** Laravel 13 app with Breeze (Blade + Alpine + Tailwind + Vite, dark mode, Pest).
  Installed & configured: sanctum, spatie medialibrary, model-states, laravel-data, translatable,
  mcamara/laravel-localization, activitylog, query-builder, knuckleswtf/scribe, Pest (+ laravel plugin).
  Published vendor configs/migrations. Ran only vendor/auth migrations (users/sessions, sanctum
  personal_access_tokens, media, activity_log; model-states has none).
- **Multi-guard auth.** Three guards/providers in `config/auth.php` — admin→users, brand→brands,
  talent→talents. Breeze auth adapted into a DRY, guard-aware structure (`UserRole` enum, `Support\Auth\Guards`,
  role-aware `LoginRequest`/`AuthenticatedSessionController`), guarded dashboard groups per entity,
  public pages unguarded. Single role-aware login (recorded as an open decision). Sanctum reserved for
  the mobile API. Added `Brand`/`Talent` Authenticatable stubs (tables land in Phase 1).
- **i18n + RTL.** Locale-prefixed routes (`/en`, `/ar`, default hidden) + `x-language-switcher`;
  middleware aliases registered in `bootstrap/app.php`. `dir`-aware layouts; Tailwind dark mode = class
  strategy. Reconciled the front-end back to Tailwind v4 (Breeze had scaffolded v3).
- **Shared spine.** JSON envelope (`ApiResponse` + `response()->success|error|paginated` macros,
  `meta.pagination`); base `Service` (with `runInTransaction` + failure logging), `Action` contract,
  `BaseData`, `BasePolicy`, `BaseResource`; dedicated log channels (app/auth/deals/media); strict models
  in non-production; `resources/js/http.js` fetch wrapper.
- **Docs.** Wrote `docs/architecture.md`, `docs/schema.md`, `docs/api.md`, `docs/conventions.md`,
  `docs/decisions.md`; refreshed `CLAUDE.md` (standing rules + pattern map) and `README.md`.
- **Tests green.** Auth scaffolding + envelope covered by Pest; no git operations.

## 2026-07-01

- **Canonical specs installed.** Added the single source of truth for the data model, pages, workflows, and lifecycles under [`docs/specs/`](specs/):
  - `docs/specs/schema-master.md` — canonical database schema (all tables, columns, constraints, relationships).
  - `docs/specs/talent-spec.md` — talent-side pages, workflows, lifecycles, and the shared deal engine.
  - `docs/specs/brand-spec.md` — brand-side pages, onboarding, workflows, and lifecycles.
  - `docs/specs/README.md` — one-page index and the rule that all schema/feature work must be checked against these specs.
  - Root `CLAUDE.md` updated to point every future session to `docs/specs/` as the canonical reference before building anything.
  - No migrations or application code scaffolded yet — that is Phase 0.
