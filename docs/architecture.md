# Architecture

> Living document — kept in sync with the code. For the data model, pages, workflows, and lifecycles,
> `docs/specs/` is the single source of truth. This file describes **how the code is layered**.

## Request lifecycle (web + API share one spine)

```
HTTP → Route (locale group, guard middleware)
     → Controller (thin: validate + orchestrate)
     → Form Request (validation) → DTO (spatie/laravel-data)
     → Service (business logic, DB::transaction, logging)
        └─ Action classes (single discrete operations)
     → Resource (BaseResource) shaped into the JSON envelope
     → response()->success|error|paginated(...)  ← ApiResponse
```

Controllers never contain business logic. They hand a DTO to a service and return a `Response`. The
**same services** back both the web-Ajax controllers and the (future) mobile API controllers, so
behaviour can't drift between the two surfaces.

## Layers & where they live

| Layer | Location | Notes |
|---|---|---|
| Controllers (thin) | `app/Http/Controllers` | Orchestrate only; return the envelope. |
| Form Requests | `app/Http/Requests` | Validation + authorization at the boundary. |
| DTOs | `app/Data` (`BaseData`) | Typed Form Request → Service → Resource contract. |
| Services | `app/Services` (`Service`) | Business logic; `runInTransaction()` wraps multi-write ops with failure logging. |
| Actions | `app/Actions` (`Contracts\Action`) | Single-purpose, invokable operations orchestrated by services. |
| Resources | `app/Http/Resources` (`BaseResource`) | Shape the `data` payload; envelope owns the wrapper. |
| Policies | `app/Policies` (`BasePolicy`) | Own-resource edits + admin override. |
| Enums | `app/Enums` | e.g. `UserRole` (role ⇄ guard source of truth). |
| Support | `app/Support` | `ApiResponse` (envelope), `Auth\Guards` (multi-guard helper). |

## Authentication — three guards

Three login entities, each its own session guard + Eloquent provider (`config/auth.php`):

| Guard | Provider | Model | Table |
|---|---|---|---|
| `admin` (default) | `users` | `App\Models\User` | `users` ✅ migrated |
| `brand` | `brands` | `App\Models\Brand` | `brands` (Phase 1) |
| `talent` | `talents` | `App\Models\Talent` | `talents` (Phase 1A) |

- **Login** is a single, role-aware form: the submitted `role` selects the guard
  (`LoginRequest::role()` → `UserRole`). Absent `role` defaults to `admin` (the only migrated table in
  Phase 0). See `docs/decisions.md` for the open UX decision.
- **Dashboards** are guarded route groups: `auth:admin` → `/admin/dashboard`, `auth:brand` →
  `/brand/dashboard`, `auth:talent` → `/talent/dashboard`. `route('dashboard')` dispatches to the
  active guard's dashboard via `App\Support\Auth\Guards`.
- **Public pages** (home now; talent/brand public profiles in Phase 1) are unguarded.
- **Mobile API** uses Sanctum tokens (`HasApiTokens` on all three models); reserved for Phase 4.

## i18n & RTL

- `mcamara/laravel-localization` wraps all web routes in a locale group (`/en`, `/ar`). The default
  locale (en) is hidden, so `/login` and `/ar/login` both resolve. Middleware aliases are registered in
  `bootstrap/app.php`; the switcher is `x-language-switcher`.
- Arabic is RTL: `<html dir>` is set from the current locale; the UI uses Tailwind **logical**
  utilities (inline-start/inline-end) so layouts mirror automatically.
- `spatie/laravel-translatable` provides per-attribute translations (policy in `docs/conventions.md`).

## Front-end & design system

- Blade + Alpine, Tailwind v4 (via `@tailwindcss/vite`), Vite. The **Fama design system** (from
  `public/fama-front`) is the visual language: `resources/css/app.css` holds the design tokens as CSS
  variables — light on `:root`, dark on `[data-theme='dark']` — and maps them into Tailwind via
  `@theme inline` so utilities (`bg-surface`, `text-ink`, `border-line`, `bg-accent`, `font-display`,
  `rounded-lg`, `shadow-e2`, …) are theme-aware. Palette: cool **cloud** surfaces, **graphite** ink,
  **teal** accent (light + dark). Fonts: **Bricolage Grotesque** (display), **Sora** (UI/body), **IBM
  Plex Sans Arabic** (Arabic + RTL headings via the dir-aware `--font-head`), **IBM Plex Mono**.
- **Dark mode** is keyed on the `data-theme` attribute (`<html data-theme="light|dark">`), matching the
  design system. A no-flash init (`partials/design-head`) sets it before paint; the toggle flips it and
  persists to `localStorage`. Tailwind's `dark:` variant is remapped to the same attribute.
- **Reusable UI** lives in `resources/views/components/ui/*` (button, card, chip, badge, avatar,
  eyebrow, stat, section) — token-bound, dark- and RTL-aware.
- **RTL**: `<html dir>` comes from the locale; headings swap to the Arabic face via `--font-head`, and
  logical utilities (`ms-*`, `pe-*`, `text-start`, …) mirror the layout for `/ar`.
- **Public talent profile** (`GET /{slug}` → `TalentProfileController` → `talent/profile.blade.php`):
  renders the **Instagram-style, avatar-led header (no cover image — ADR-O)** — circular avatar,
  display_name + @username, primary-skill/headline line, a Projects · Views · Rating stats row, bio,
  optional link, the Pricing-rate chip, and Contact / Leave-a-review CTAs — then `profile_blocks` in
  `position` order (skipping the `hero` block), each dispatched to a `talent/blocks/{key}` partial
  (gallery, comp_card, reviews, brand_collabs, looks, digitals, showreel, equipment, …) reading from
  eager-loaded relations. Missing media falls back to
  tasteful gradient/initials placeholders.
- `resources/js/http.js` is the shared fetch wrapper: it attaches CSRF + `X-Requested-With` +
  `Accept: application/json`, parses the envelope, and throws `ApiError` (with the field error bag) so
  pages surface validation errors without reloading.

## Domain model — talent side (Phase 1A)

The talent "living creative passport" is a core (`talents`) plus a **malleable block system** and a set
of content tables (`app/Models`, schema in `docs/schema.md`).

- **Skills.** `talents` ⇄ `talent_types` many-to-many via `talent_talent_type` (`is_primary` leads
  the headline, `position` orders). Each `talent_type` carries `default_blocks` (ordered block keys).
  `talent_types` is the **Skills catalog** — "Skills" is the product term; the table name is unchanged
  (ADR-N). **Nine** skills since ADR-Y.
- **Project credits (ADR-AA).** `project_credits` — `person + skill + role`. An ON-PLATFORM credit
  needs the tagged person's confirmation and is invisible until accepted; an OFF-PLATFORM one (a typed
  name) is `listed` immediately. `Talent::portfolioProjects(?scope)` is THE portfolio view: owned
  projects in that scope plus accepted credits, filed under the CREDIT's skill.
  `projects.occurred_on` is the date — recorded for ordering, never rendered — and `year` is a derived
  projection of it (not fillable).
- **The six portfolio sections (ADR-Z).** Now (`will_travel` / `is_available` /
  `availability_changed_at` on `talents`) and About (`worldview` + `talent_social_handles`) render in the
  profile header; Recognitions (`recognitions` — peer vouches, talent → talent, the sibling of
  brand → talent reviews) and Willing to explore (`talent_interests` — a skill + optional speciality)
  are BLOCKS. `block_types.is_profile_default` marks the blocks seeded once at profile level rather than
  once per skill tab.
- **Specialities (ADR-Y).** The second and final level: `specialities` (owned by one skill, `slug` unique
  **per skill**), claimed by talents through `speciality_talent` and by projects through
  `project_speciality`. A **controlled** vocabulary — pickers only, never free text — so the same name
  matches on both the portfolio and the project side and discovery keeps working.
- **Block catalog.** `block_types` is the admin-governed catalog. `availability` gates who can add a
  block: `universal` | `by_category` (→ `block_type_category`) | `by_type` (→ `block_type_talent_type`).
  `content_source` says whether the block stores data inline (JSON) or in a rich content table.
- **Layout layer.** `profile_blocks` is the per-talent arrangement (position, visibility, layout, inline
  content). On profile creation the merged+deduped `default_blocks` of all linked types seed the blocks;
  `blockType` is always eager-loaded (rendering needs it). Rich blocks read from the content tables
  (`portfolio_items`, `comp_cards`, `showreels`, `projects`, …).
- **Media.** Every model holding uploaded files is `HasMedia` with single-file collections + a `thumb`
  conversion; `*_url` accessors resolve from the library (ADR-5, list in `docs/conventions.md`). External
  links/embeds stay as plain columns.
- **Seeders.** `TalentTypeSeeder` (the 41 skills) + `SpecialitySeeder` (195 entries — must run AFTER it) both
  delegate to `App\Actions\Catalog\SyncSkillCatalog`, which applies `database/data/skill-catalog.php` (THE catalog,
  identical to the prototype's skills.js — ADR-AF). The catalog migration runs the same action on an existing DB.
  Unlisted specialities are retired (is_active=false), never deleted; skill slugs never change.
  `BlockTypeSeeder` (catalog, incl. the `is_profile_default` flags) is the rest of the canonical
  reference data; `TalentDemoSeeder` builds one rich multi-type (model + photographer) talent for later
  phases to render.

> Not yet built: the block-seeding/merge logic will move into an Action + Service in the profile-editor
> phase (currently expressed inline in `TalentDemoSeeder`).

## Talent domain logic — services, actions & state machines (Phase 1B)

Business logic lives in **services** (orchestration + transactions) and single-purpose **actions**;
lifecycles are **state machines**; side effects are **events**.

**Actions** (`app/Actions`, invokable):
- `SeedBlocksForSkill` — seed **one skill's** `default_blocks` into that skill's tab (stamped with
  `talent_type_id`, positioned + de-duped **within the scope**), then move the profile `Created → Draft`
  (ADR-Q; replaces the old global-merge `MergeDefaultBlocksForTypes` / `SeedProfileBlocks`).

**Services** (`app/Services`, extend `Service`; every multi-write op is `runInTransaction` + fail-logged):
- `ProfileBlockService` — **scope-aware** (ADR-Q). `availableBlockTypes($talent, ?$scope)` is the
  per-scope picker (active + eligible in that scope − non-repeatable already present there; universal
  blocks in any tab or the universal section, gated blocks only in an eligible skill's tab); `addBlock`
  (into a scope), `fillBlock`, `reorder` (within a scope), `moveBlock` (re-stamp `talent_type_id`,
  validated for eligibility + per-scope repeatability), `setVisibility`, `removeBlock`. Rendering
  resolves via `block_type_id → block_types`, so deactivated (grandfathered) blocks still render.
- `SkillsService` — `addType` (seeds **that skill's** tab via `SeedBlocksForSkill`), `removeType`
  (deletes the tab's blocks but **preserves content** — items un-linked, and `untagProjects` drops the
  skill's tag from each project, re-homing it on its **next remaining tag** and un-scoping only the ones
  that ran out, ADR-X — and logs it; also detaches that skill's speciality claims, ADR-Y), `setPrimary`,
  `reorderTypes`, `setSpecialities` (per-skill replace; **the** place the "picks belong to a skill you
  hold" rule lives — 422 otherwise). (Skills manager; renamed from `ProfessionsService` — ADR-N.)
- `RecognitionService` — the ONLY write path for peer vouches (ADR-Z): `submit` (self-vouch denied;
  a re-submission REPLACES the earlier one and resets it to pending, respecting the
  `(talent_id, author_talent_id)` unique index), `approve` / `reject` (state transitions —
  `is_approved` follows via `SyncStateProjections`, never set by hand), `withdraw` (soft delete, so a
  later re-submission restores the same row).
- `ProjectCreditService` — the ONLY write path for collaborator credits (ADR-AA): `add` (on-platform →
  pending + notify; off-platform → listed), `accept` / `decline` (state transitions; accept also calls
  `ensureProjectsBlock()` so the confirmation actually surfaces the work), `remove`, `reorder`.
  Notifications are dispatched POST-COMMIT and guarded, so a notifier bug shows in the log rather than
  the response.
- `TalentInterestService` — the ONLY write path for "willing to explore" (ADR-Z). `assertShape()` checks
  that a speciality belongs to the chosen skill; it deliberately does NOT check that the talent holds
  that skill.
- `TalentProjectService` — the ONLY write path for the talent's portfolio projects (ADR-X):
  `create`/`update` (skill tags synced in the same transaction, tag 0 mirrored onto the
  `talent_type_id` home column), `delete`, `reorder`, `setCover`/`removeCover` (single-file `cover`
  collection, `media` log channel). `assertOwnedSkills()` holds THE rule — at least one skill, all of
  them the talent's own — so a caller that bypasses `StoreProjectRequest` still cannot break it. Rich
  text (`summary`/`body`) is sanitized with `BriefSanitizer`, and `results` is normalised from the
  editor's label/value rows into the stored metric map.
- `TalentProfileService` — core fields, the Pricing rate (`updatePricingRate`, all-or-nothing),
  publish/unpublish, reviews moderation. *(The hero/cover uploader was removed — ADR-O.)*
- `ProfileViewStatsService` — read-model over `talent_profile_views` for the dashboard views KPI:
  `weeklyComparison` (rolling last-7-days vs the 7 before; `delta_pct` null when the previous window
  is empty) and `series` (range presets `7d`/`30d`/`90d`/`all` — 'all' reaches back to the profile's
  `created_at`; bucket = day/week/month chosen from the span; gaps zero-filled; labels localized
  server-side via `translatedFormat`).

**State machines** (`app/States`, spatie/laravel-model-states). Each has explicit allowed transitions;
the state is authoritative and a synced boolean/timestamp **projection** (kept by `SyncStateProjections`)
serves the Phase 1A queries/views.

| Machine | Column | States | Projection |
|---|---|---|---|
| TalentProfile | `talents.status` | created → draft → live ⇄ unpublished → suspended/archived | `is_published` + `published_at` (via guarded `ToLive`) |
| Block | `profile_blocks.status` | visible ⇄ hidden | `is_visible` |
| Review | `reviews.status` | pending → approved \| rejected | `is_approved` |
| PortfolioMedia | `portfolio_items.status` | uploaded → processed → ordered → visible → archived | — |

*(The Availability, Service, and Affiliation state machines were removed with their features — ADR-K/L/M.)*

**Events / listeners** (auto-discovered in `app/Listeners` by handle() type-hint):
- `TalentProfileViewed` → `IncrementProfileViewCount` (bumps `view_count`; controller dispatches it)
  **and** `RecordDailyProfileView` (atomic `INSERT … ON DUPLICATE KEY UPDATE` into the
  `talent_profile_views` daily bucket — MySQL-only, feeds the dashboard views chart).
- spatie `StateChanged` → `SyncStateProjections` (projections + published_at stamp).
- medialibrary `MediaHasBeenAddedEvent` → `LogMediaUploaded` (conversions auto-queue).
- medialibrary `ConversionHasBeenCompletedEvent` → `AdvancePortfolioMediaState` (uploaded → processed).

**Policies** (`app/Policies`, auto-discovered): a talent may only manage its own resources
(`TalentPolicy`, `ProfileBlockPolicy`, `ReviewPolicy`, `PortfolioItemPolicy`), all via
`BasePolicy::owns`.

### Prototype sync, 2026-11-04 (ADR-AC, ADR-AD)
- **Shell:** `components/fama/nav` renders the talent side bar (≥1101px) + top bar (≤1100px); `famaSide` /
  `famaNav` (resources/js/ui/shell.js) share `Alpine.store('famaNotifs')` (unread count, latest list, read/readAll).
- **Go Live:** `ProfileCompletionService` (goLiveSteps · standOutSteps · canGoLive · isListed · isProfileDone ·
  homeRoute · state · syncAfterWrite) + `App\Actions\Talent\SyncGoLiveState` (Created/Draft → Live when ready;
  Live → Draft when a save breaks a step). `GET /talent/profile/state` feeds the editor's steps from SAVED data.
- **Landing:** `AuthenticatedSessionController` + `FeedController::home` use `homeRoute()` for talents.
- **Notifications page:** `NotificationInboxService` (filters, one aggregate counts query, kind map, bulk
  follow-back lookup) behind `NotificationController::index`.
- **Titles:** `App\Support\ProjectTitle` (name · sub · year tag) shared by the profile grid, featured cover and
  project page.

### Project stories as free-form blocks (2026-11-17, ADR-AN)
A project's story is `projects.blocks`. Write path: `StoreProjectRequest` (shape via `ProjectBlocks::rules()`) →
`TalentProjectService::create/update` (`ProjectBlocks::normalize` checks media ownership + link hosts, derives
`summary`/`body`, then `syncStoryMedia` orders the `media` collection by the story and marks/deletes pictures it no
longer shows). Read path: `Project::storyBlocks()` → `ProjectStory::for()` (layout decisions) → the
`public/partials/project-story` view; behaviour in `famaProjectPage` (slider, look tags, click-to-play video) and the
GSAP layer `project-motion.js` (Cases pin, quote reveal). Editor: `famaProjectEdit` in `resources/js/ui/project-editor.js`.

### SEO (2026-11-19, ADR-AO)
`App\Support\Seo` (per-page metadata) → `partials/seo` inside `<x-fama.layout>`; `RobotsTag` middleware mirrors the
robots decision as a header for every web response. Crawler files: `SeoController` (thin) → `SitemapService` /
`LlmsTxtService` (cached strings), routes at the top of routes/web.php outside the locale group.

## Talent dashboard (Phase 1C)

The authenticated talent-guard dashboard (`routes/talent.php`, controllers in
`app/Http/Controllers/Talent`, all extending `TalentController`). It is Blade shells + Alpine driven by
the shared `http.js` wrapper — pages render a shell, every interaction is Ajax against JSON-envelope
endpoints, nothing reloads.

- **Thin controllers → services.** Controllers validate (Form Requests in `app/Http/Requests/Talent`),
  call the Phase 1B services (ProfileBlockService / SkillsService / TalentProfileService), and
  return Resources (`app/Http/Resources`) wrapped in the envelope. `TalentController::ensureOwns()`
  enforces own-resource access (403); `BlockContentController` resolves the model then `ensureOwns`.
- **Sidebar (ADR-N).** Reduced to **Home · Profile · Content · Reviews · Contracts** — the standalone
  Professions and Account tabs were folded into the Profile editor.
- **Pages.** Home (stats + contracts slot); the **Profile editor** — the single profile surface: identity +
  **Username** (`slug`), the **Skills** section (`SkillController` under `/talent/profile/skills*`),
  the **Pricing rate**, the **Publish** toggle (`PATCH /talent/profile/publish`), and the reorderable
  blocks + eligibility picker; Block content editors (a registry-driven controller serving
  every "table" block — gallery/digitals/showreel/equipment/projects/software/brand-collabs/looks — with
  medialibrary upload); Reviews moderation. *(The hero/cover uploader was removed — ADR-O.)*
- **Front-end** (`resources/js/dashboard.js`, Alpine): `profileEditor` (core + username + Skills +
  pricing + publish + blocks; optimistic drag-reorder, inline errors — the old
  `professionsManager` is folded in) and a generic `crudList` (paginated load,
  create/remove/act, media quick-add, drag-reorder). `x-talent-layout` is the sidebar shell (dark + RTL).
- **Error envelopes.** `bootstrap/app.php` renders `ValidationException`/`InvalidArgumentException`/
  `CouldNotPerformTransition` as 422 (and `AuthenticationException` as 401) for JSON/Ajax requests, so
  the front-end surfaces them inline.

## Public pages & discovery (Phase 1C)

Unguarded, locale-prefixed pages (`routes/web.php`, controllers in `app/Http/Controllers`), rendered
through `x-public-layout`. All resolve **published** talents only.

- **Talent profile** (`TalentProfileController` → `talent/profile`) — eager-loads a published talent
  with everything the profile renders (visible blocks in position order, approved reviews, active
  services, all content tables + media), bumps `view_count` via the `TalentProfileViewed` event, and
  stays presentational. Project blocks link to their detail page; the header carries a review CTA.
- **Project** (`ProjectController` → `public/project`) — one `projects` record expanded,
  404 unless it belongs to the (published) talent.
- **Review submission** (`PublicReviewController` + `StoreReviewRequest` → `public/review`) — an Ajax
  form that writes a pending review; the talent moderates it from the dashboard queue.
- **Discovery** (`DiscoveryController` → `public/discover`, `talentSearch` Alpine) — a Blade shell whose
  results come from an Ajax endpoint backed by **`App\Queries\TalentSearch`**, a query object over
  spatie/laravel-query-builder. Filters (type/category through the pivot, city, country,
  equipment, software, free-text) map to `filter[...]` params; results are paginated, eager-loaded
  (`talentTypes` + `media`), and shaped by `TalentCardResource`. Backed by the Phase 1C search indexes
  (see docs/schema.md).

## Contract engine (Phase 1E — shared infrastructure)

The configurable brand ↔ talent contract loop. Admin-authored flows (`contract_flows` +
`contract_flow_steps`) are **snapshotted** into a contract's `contract_steps` at creation (ADR-4), so template
edits never affect in-flight contracts. Brand (Phase 2) and Admin (Phase 3) extend the same engine.

**Strategy + Factory — one handler per `step_type`** (`app/Contracting/Steps`, resolved by
`App\Contracting\StepHandlerFactory`). Each handler `validate()`s the actor's input, `apply()`s side effects
to the contract, declares `isAutomatic()`, and returns a `summary()` for the timeline:

| step_type | handler | behaviour |
|---|---|---|
| form | FormStepHandler | validates `fields`; an `amount_field` sets `contract.agreed_amount` |
| approval | ApprovalStepHandler | approve → advance; reject → loop back (separate path) |
| upload | UploadStepHandler | requires ≥1 `attachments` reference |
| payment | PaymentStepHandler | **ADR-B**: `settings.confirmation` = manual \| auto (default **manual**); auto (or system actor) auto-completes |
| contract | ContractStepHandler | records a signature |
| message | MessageStepHandler | requires a body; echoes it into the thread |
| schedule | ScheduleStepHandler | writes `start_date`/`end_date` onto the contract |
| info | InfoStepHandler | system-actor → auto-complete; human → acknowledge |

**Actions** (`app/Actions/Contracting`, single-purpose invokables): `SnapshotContractFlowSteps`, `InitiateContract`
(create + snapshot + activate first), `AdvanceContract` (validate/apply → complete → advance), `RejectStep`
(loop back — reopen the disputed step, reset the tail to pending), `ConvertEnquiryToContract`.

**ContractProgression** (`app/Contracting`) is the engine the actions share. It holds the invariants: exactly one
step active/awaiting_action at a time, `contract.status` mirrors the current step's actor
(awaiting_brand ⇄ awaiting_talent ⇄ awaiting_admin), automatic steps complete themselves and recurse,
every completion posts a `system_event`, and running out of pending steps completes the contract.

**State machines** (`app/States/Contract|ContractStep|ContractMessage`, spatie/laravel-model-states):
- **Contract**: draft → awaiting_* (interchangeable) → completed; terminal cancelled/declined/expired; soft-delete.
- **ContractStep**: pending → active → awaiting_action → completed; side exits skipped/rejected; reject-loop
  uses completed→rejected→awaiting_action (redo) and completed/awaiting_action→pending (tail reset).
- **ContractMessage**: sent → read (`read_at` is the projection); system_event/action_summary are immutable
  (never marked read).

**ContractService** (`app/Services/ContractService`, `contracts` log channel) is the single façade controllers call:
`initiate`, `advance`, `reject`, `skip`, `convertEnquiry`, `postMessage`, `markThreadRead` — each wraps
its action(s) in a transaction with fail-logging.

**Booking CTA / contract initiation**: the public profile Contact button → `EnquiryController` writes a
`contract_enquiries` row (always allowed — no availability gate, ADR-L; no login); it converts to a contract
after the visitor authenticates as a brand (Phase 2). The contract amount comes from the flow's form/quote
step, not a service (ADR-K). Talent contract UI: `Talent\ContractController` (contract room + inbox) acts as
the `talent` role; the Alpine `contractRoom`/`contractsInbox` components (`resources/js/contracts.js`) render a
turn-aware action panel by `step_type` and the interleaved message/system_event timeline.

> **Brands stub**: `contracts.brand_id` needs a `brands` table, which is Phase 1B. Phase 1E ships a
> **minimal** brands table (auth surface + name/slug + `is_complete` gate) so the engine references and
> tests can seed brands; Phase 1B adds the full brand core (see docs/schema.md).

## Domain model — brand side (Phase 2A)

The brand core (`brands`, extended from the Phase 1E stub) plus its satellites and projects
(`app/Models`, schema in `docs/schema.md`). Schema layer only — services + state machines + the brand
dashboard are Phase 2B/2C (same split as talent 1A → 1B).

- **Identity & gates.** `brands` holds the public identity; onboarding fills it progressively (nullable
  fields). `is_complete` gates transacting, `is_published` gates talent-visibility, `is_verified` is a
  one-way admin trust upgrade. `description` is translatable; logo + cover are medialibrary collections.
- **1:1 satellites.** `aesthetic` (`BrandAesthetic`), `creativeNeed` (`BrandCreativeNeed`), `credibility`
  (`BrandCredibility`) — created once, updated in place.
- **Child collections.** `images` (medialibrary), `brandReviews` (three sub-ratings, `average_rating`
  accessor), `socialHandles`, `signals` (append-only), `brand_projects`.
- **ADR-6 discovery pivots.** Mood tags → `brand_mood_tags` (via the aesthetic); creative-need talent
  types → `brand_creative_need_talent_type` (M:N with `talent_types`); project types →
  `brand_creative_need_project_type`. All indexed so the feed can filter "brands needing photographers /
  with an editorial mood / running campaign videos".
- **Projects.** `BrandProject` (translatable description + brief, cover media, soft-delete, `status`
  lifecycle) `belongsTo` a **single** `TalentType` via `brand_projects.talent_type_id` and `hasMany`
  `BrandProjectMedia` (`gallery()`, uploads via medialibrary). **One project = one role = one
  position** — the old `brand_project_talent_types` pivot (many roles × `quantity`) is gone.
  `budget_is_public` (default **false**) gates the budget: private budgets are visible to the owning
  brand only and are stripped from every public/talent-facing payload, not just hidden in the view.
  A project groups many contracts via `contracts.brand_project_id` (ADR-F — **landed**).
- **Demo data.** `BrandDemoSeeder` builds a full brand (Nomad Coffee) — aesthetic + moods, creative
  needs + pivots, credibility, images, social handles, a talent review, and a public project with its
  role + gallery — enriching the same brand the contract seeder uses.

## Brand domain logic — services, state machines & events (Phase 2B)

Business logic in **services**; lifecycles are **state machines**; accrual is **event-driven**. Every
multi-write op runs in `runInTransaction` with fail-logging to the **`brands`** channel.

**State machines** (`app/States/Brand|Project|BrandReview`, spatie/laravel-model-states — `status`
authoritative, flags are synced projections via `SyncStateProjections`):
- **Brand**: registered → onboarding → complete → published ⇄ unpublished; suspended from any complete
  state; soft-delete removes it. `is_complete`/`is_published`/`is_active` are projected from status;
  **`is_verified` is orthogonal** — a one-way admin flag, not a status.
- **Project**: draft → open → in_progress → completed; cancellable from any active state. `is_public`
  toggles independently of status; `Project::showcase()` = completed + public.
- **BrandReview**: pending → approved | rejected (mirrors talent reviews); `is_approved` synced.

**Services** (`app/Services`):
- `BrandOnboardingService` — the 6-step wizard (identity → location → creative needs → aesthetic+images
  → budget → complete). Step 1 moves registered → onboarding; step 6 flips `is_complete`. Idempotent
  per step.
- `BrandProjectService` — create/edit, `syncRoles` (talent types + quantity), `addMedia`, and the status
  transitions (open/start/complete/cancel, `setPublic`).
- `BrandReviewService` — talent `submit` (guards contract completed + one-per-contract → pending), admin
  `approve`/`reject`. No edit path (the brand can never edit a review).
- `BrandSignalService` — append-only view/save/brief_sent/profile_open writes.
- `BrandCredibilityService` — wraps the `RecalculateBrandCredibility` action.

**Accrual (event-driven).** `ContractProgression` fires **`ContractCompleted`** when a contract reaches `completed`;
the auto-discovered **`AccrueBrandCredibility`** listener recomputes the brand's credibility
(`RecalculateBrandCredibility`): monotonic `completed_projects_count`, `response_rate_pct`,
`avg_response_time_hours`, internal `brief_quality_score`. Automatic — the brand takes no action. The
same completion opens the talent's brand-review window (talent-initiated).

**Discovery feed** (`App\Queries\BrandTalentFeed`) — a personalised talent feed seeded from the brand's
creative-need talent types (the ADR-6 pivot) + `geographic_reach`, on spatie/laravel-query-builder;
paginated + eager-loaded; browsing writes a `view` signal. Aesthetic (mood) weighting is deferred — a
hard filter needs a talent-side aesthetic signal, so `brand_aesthetics` informs later ranking, not
selection.

> Update-in-place satellites (aesthetics, creative needs, images, social handles) have no terminal state
> of their own; `brand_signals` is append-only. Contracts link to a project via `contracts.brand_project_id` (ADR-F).

### Brand dashboard (Phase 2C)

The brand-guard dashboard (`routes/brand.php`, `app/Http/Controllers/Brand/*`, `<x-brand-layout>`,
`resources/js/brand.js`) mirrors the talent dashboard: Blade shells + Alpine on `http.js`, JSON envelopes,
no reloads, ownership 403 / domain 422. Controllers are thin and delegate to the Phase 2B services — the
6-step **onboarding wizard** (persists per step, flips `is_complete`, then drops the brand into its first
feed), **profile editor** (core + aesthetic + images + social), **creative-needs** editor, **projects**
manager + workspace (roles, media, lifecycle buttons, the contracts running under the project), the
**discovery feed** (infinite/paginated via `BrandTalentFeed`, save/brief write signals), the **brand contract
room** (the `brand` side of the shared engine — `awaiting_brand` highlighted, action panel keyed by
`step_type`), read-only **reviews received**, and **account** (settings + publish toggle). An incomplete
brand is redirected into onboarding by the dashboard.

## Admin domain logic — governance (Phase 3A)

The platform-governance layer sits on the Phase 3A foundation (admin guard + spatie RBAC + settings +
activitylog). Every admin service extends **`App\Services\AdminService`** (base `Service`): it runs on the
`admin` log channel, **authorizes** the acting admin against a policy/permission (`authorizeAdmin()` →
`Gate::forUser($admin)->authorize()`, or `authorizePermission()` for bare permissions), and **records**
each action to the activity log with the admin as causer. Everything is transactional + fail-logged.

**Policies** (auto-discovered) map each capability to a spatie permission on the admin guard:
`ContractFlowPolicy@manage` / `TalentTypePolicy@manage` → `manage-flows`; `TalentPolicy@moderate` /
`ReviewPolicy@moderate` / `BrandPolicy@moderate` / `BrandReviewPolicy@moderate` / `BrandProjectPolicy@oversee`
→ `moderate-content`; `ContractPolicy@intervene` → `intervene-contracts`; media/settings → `manage-settings`.

**Services:**
- **`ContractFlowBuilderService`** — author `contract_flows` + ordered `contract_flow_steps`, reorder, and drive the
  **template state machine** (draft → active → archived; `is_active` synced, `is_default` an orthogonal
  flag kept unique per `applies_to` scope). Edits touch **future contracts only** — contracts snapshot their steps
  at creation (Phase 1E), proven by test. Flow/step changes are audited by their `LogsActivity` traits.
- **`TalentModerationService`** (suspend / unpublish / soft-delete / restore) and
  **`BrandModerationService`** (verify one-way + suspend / unpublish / soft-delete) drive the existing
  state machines with `canTransitionTo` guards.
- **`ReviewModerationService`** — approve/reject talent reviews (incl. **batch**) and brand reviews.
- **`ProjectOversightService`** — filter by status, cancel, force-private.
- **`SkillCatalogService`** — edit a talent type's ordered `default_blocks` PRESELECTION (affects only
  NEW seeds) and add skills without code. Per **ADR-T** it may only ADD catalog-eligible keys
  (`ProfileBlockService::isEligibleForScope` — the same predicate the talent picker uses); stale keys may
  be reordered/removed.
- **`BlockCatalogService`** — the eligibility side of the ADR-T split (`manage-blocks`): create/edit
  `block_types` (availability gates synced to the active mode), toggle `is_active` (grandfathered —
  existing `profile_blocks` keep rendering), validate `settings_schema` JSON, and lock `key`/
  `content_source` once the type is in use (422).
- **`MediaOversightService`** — surface media with ungenerated conversions and re-queue them
  (`media-library:regenerate`).
- **`ContractInterventionService`** — reuses the 1E engine: act as the **admin actor** on admin steps,
  **override** a stuck step (`ContractProgression::finishStep` + `activateNext`), nudge (system event),
  reassign, or cancel (`moveDealTo`). Every override posts a system event + activity log.

> Audit: `ContractFlow`/`ContractFlowStep` self-log field changes (`attribute_changes`); moderation/intervention
> services log explicit action entries (`moderation` / `catalog` / `media` / `contract_intervention` log
> names) with `causedBy($admin)` + ad-hoc `properties` (reason, from/to, etc.).

### Admin dashboard (Phase 3B)

The admin-guard dashboard (`routes/admin.php`, `app/Http/Controllers/Admin/*`, `<x-admin-layout>`,
`resources/js/admin.js`) wires the 3A services into a UI, following the same conventions as the talent/brand
dashboards (Blade shells + Alpine on `http.js`, JSON envelopes, no reloads, eager-load + paginate).
**Authorization is two-layered:** `can:` middleware gates each route group per permission (a powerless admin
gets 403 before the controller runs), and the service re-authorizes + audits the action.

**Reachability (2026-07-16):** every admin page has a grouped, permission-gated sidebar link AND a
dashboard card/quick link. The sidebar groups Dashboard · Moderation (Talent profiles / Brands / Brand
reviews / Global review queue — each deep-links its tab via server-validated `?queue=`, kept in sync with
replaceState; `aria-current` marks the active item) · Marketplace (Projects oversight / Contracts) ·
Configuration (Contract flows / Skills templates / Block catalog) · System (Activity / Settings / Admins).
The dashboard home renders moderation queue counts (with "All clear." empty states), a contracts
whose-turn card, a projects-by-status card, governance quick links, and recent activity — each section
computed only when the admin holds its permission, via single aggregate queries. `resources/js/admin.js`
is imported by `app.js` (it must stay in the Vite bundle — without it every admin page is a dead Alpine
shell that HTTP-level tests cannot detect).

Screens: the **flow builder** (drag-reorder steps, configure actor/type/flags/settings, set default,
scope, activate/archive), tabbed **moderation queues** (talents / **all-reviews** (global, both kinds via
UNION + per-kind hydration) / reviews / brands / brand-reviews / projects with batch approve/reject +
suspend/verify/unpublish/cancel, a projects status filter, and admin-visible budgets tagged private), the
**skills template manager** (ordered `default_blocks` preselection among catalog-eligible blocks, stale
keys flagged), the **block catalog manager** (existence + eligibility + config, grandfathering — ADR-T),
the **contract intervention console** (status + current-step filters; override / advance-as-admin / nudge /
cancel with a live timeline), a searchable **activity-log viewer**, the **settings** screen, and
**admin-user** management (create + role assignment). Controllers stay thin — every mutation delegates to
a Phase 3A service.

## Feed domain (Phase 5 — in progress; canonical spec `docs/specs/feed-spec.md`)

- **Models** (`app/Models`): `Post`, `Comment`, `Like`, `Follow`, `Mention`, `LinkPreview`,
  `LinkedUrl`, `PostReport`, `FeedSeen` — every actor-side reference polymorphic (ADR-F1). `Post` is
  `HasMedia` (`post_media` collection; queued WebP conversions `thumb` 320² / `feed` 1080w / `full`
  1600w) with `scopePublished()` and `isEditable()` (the 15-minute window, `config/feed.php`).
  `Talent` and `Brand` share **`HasFeedIdentity`** (`app/Models/Concerns`): `posts`, `feedComments`,
  `followers`, `following`, `likesGiven` + identity readers (`feedUsername/feedDisplayName/
  feedAvatarUrl`) — the raw material for the Actor contract (5C).
- **State machines** (`app/States/Post`, `app/States/Comment`): `PostState` draft → published ⇄ hidden
  → removed (terminal); `CommentState` born published, hidden ⇄ published, removed terminal. Author
  deletes are soft deletes, orthogonal to moderation states (D12).
- **Counters (ADR-F6):** `CounterService` is the only mutator, invoked from `LikeObserver` /
  `CommentObserver` / `FollowObserver` / `PostObserver` (registered via `#[ObservedBy]`), so counters
  move inside the writing transaction; decrements clamp at zero; `posts_count` tracks the published
  boundary in both directions. `applyPostPublishDelta` ALSO maintains **`talents.last_posted_at`**
  (the dashboard "quiet since" signal): a publish stamps the later of the stored value and the post's
  `published_at`; leaving the published state (delete / moderation hide) recomputes from the remaining
  published posts, so removing the LAST post clears the column. Nightly `feed:reconcile-counters`
  (03:10) recomputes every counter from source — including `last_posted_at` via a NULL-safe `<=>`
  repair — and reports drift to the `feed` log channel.
- **Morph map (ADR-F1):** `Relation::enforceMorphMap(App\Support\MorphMap::map())` in
  `AppServiceProvider` — aliased snake_case keys for every model (+ `media`, `role`, `permission`).
  Migration `2026_07_22_000200` converted pre-existing FQCN morph rows. Aliases are frozen; a new
  model that can appear in any morph column MUST be added to `MorphMap` or writes throw.
- **Config:** every feed tunable lives in `config/feed.php` (edit window, limits, auto-hide
  threshold, `allow_following_brands`) — no magic numbers in feed code.
- **Actor plane (5C/5K):** `App\Feed\Actor` (Talent + Brand) + stateless `CurrentActor`
  (talent → brand → sanctum priority; admins/guests null); middleware `resolve.actor` (shares
  `$currentActor`, never rejects) / `require.actor:<action>` (guest → 401 envelope with
  `data.intent` + session capture of the intent AND the `feed.return_to` anchor; admin → 403).
  `PendingIntent` replays the captured like/follow/comment exactly once on the `Login` event, and
  the role-aware login honours `feed.return_to` (a deliberately separate slot from `url.intended`).
  Policies: Post / Comment / Like / Follow / PostReport (auto-discovered); `PostPolicy::create` is
  the single point where brand posting would be enabled (ADR-F1). Feed controllers extend
  `FeedBaseController` and never touch `Auth::guard()`. Routes: `routes/feed.php` (inside the
  locale group **before** the `/{slug}` catch-all) + the `/api/v1` mirror (`routes/api.php`,
  Sanctum) through the same controllers/requests/actions/Resources.
- **Ranking (5I/5P):** `FeedService` — frozen candidate-ID list per viewer+seed+filters (cursor
  `{s,o,k,f}` carries seed, offset, skill, following); seeded-shuffle tier 1, scored tier 2,
  interleave, diversity cap; `DiscoverTalentsService` casting strips (ranked-ID cache only — never
  cached models). Budgets + EXPLAIN evidence + cache inventory: `docs/performance.md`.
- **Rich text (5D/5E):** `RichTextParser` (multibyte offsets) → `SyncMentions`/`SyncLinks`
  (idempotent full re-parse) → `BodyRenderer` (escape-first, current-Username, `<bdi>`+isolates);
  `App\Feed\Links` driver registry + `FetchLinkPreviewJob` behind the `UrlGuard` SSRF wall.
- **Notifications (5J):** `App\Feed\FeedNotifier` is the only dispatch point (self-guard, 1 h like
  aggregation in place, mention suppression, follow-window, Project-share fan-out); 9 queued
  classes under `app/Notifications/Feed`; payloads are message keys localized at read.
- **Moderation (5Q):** `FeedModerationService` (state machines + `feed_moderation` activity log +
  author `ContentRemoved` notices + the auto-hide tripwire) behind `/admin/moderation/feed`;
  nightly `feed:reconcile-counters` + `feed:prune`.
- **UI (5L–5P):** the scoped Daylight system (`docs/design-system.md`), the canonical frame partial
  (`feed/partials/post`), the spine + composer + inline threads (`resources/js/feed.js`), filtered
  modes `?skill=`/`?following=1`, and the `/design/feed-tokens` review page.
- **Dashboard follow management (Prompt 5-follow):** the FOLLOWING / FOLLOWERS sub-tabs on both the
  talent and brand dashboards (`Talent\FollowsController` + `Brand\FollowsController`,
  `/{guard}/feed/follows[/data]`, shared partial `partials/feed-follows` on a `data-surface="daylight"`
  region so the rail card language — portrait talents, square brands, Fraunces display, mono handles,
  `--ink-faint` — applies inside the dashboard shell). `FollowManagementService` pages the follow
  graph (24/page) with the polymorphic other party eager-loaded via **`MorphTo::morphWith`** (talents
  with Skills + media, brands with logo — a plain `with()` N+1s across the mixed types; a
  fixed-query-count test guards it), resolves mutuality in ONE bulk `FollowService` query
  (`isFollowing` on the followers side, the new `followsBack` on the following side), and shapes two
  management-only signals — **FOLLOWS YOU BACK** (mutual) and **QUIET SINCE <month>** (talents silent
  > 90 days, from `last_posted_at`). Search (name + Username), a type filter (talents/brands; hidden
  for brands, who follow only talents), a Skill filter populated from Skills PRESENT in the set
  (`presentSkills`, not the catalog), and sorts (recently followed / alphabetical / least-active by
  `last_posted_at` ASC) all resolve in SQL for correct pagination. Unfollow is optimistic (dim +
  280 ms slide-out, 5 s undo that restores the row in place); the FOLLOWERS tab offers FOLLOW BACK
  where not mutual, never unfollow. These dashboard signals never enter the public
  `FollowListController` payload (asserted).

## Cross-cutting

- **Release feature flags (ADR-W):** `config/features.php` (`brands`, `contracts`, `messaging`, each
  `env('FEATURE_*', true)`) scope a release. Read via `App\Support\Features`; enforced at request time by the
  `feature:<name>` middleware (`App\Http\Middleware\EnsureFeature`, disabled area → 404) and the
  `@feature('brands')` Blade directive (registered in `AppServiceProvider::boot`). Default ON (full app);
  production V1 sets them false to ship talent + feed + public profile only. Nav arrays carry a `feature` key
  and filter; `config/feed.php` `allow_following_brands` tracks `FEATURE_BRANDS`.
- **Logging:** dedicated channels `app`, `auth`, `contracts`, `media`, `feed` (`config/logging.php`).
  Failure convention: catch → log to channel with context → rethrow / return error envelope
  (`Service::runInTransaction`).
- **Transactions:** multi-write operations run through `Service::runInTransaction()` /
  `DB::transaction()`.
- **Strict models:** `preventLazyLoading` + `preventSilentlyDiscardingAttributes` in non-production
  (`AppServiceProvider`).
- **Audit:** `spatie/laravel-activitylog` (table migrated) for admin edits/moderation/overrides.

See `CLAUDE.md` for the full pattern map (which pattern/package backs which part of the domain).
