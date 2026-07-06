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
  `rounded-lg`, `shadow-e2`, …) are theme-aware. Fonts: Bodoni Moda (display serif), IBM Plex
  Sans/Arabic/Mono.
- **Dark mode** is keyed on the `data-theme` attribute (`<html data-theme="light|dark">`), matching the
  design system. A no-flash init (`partials/design-head`) sets it before paint; the toggle flips it and
  persists to `localStorage`. Tailwind's `dark:` variant is remapped to the same attribute.
- **Reusable UI** lives in `resources/views/components/ui/*` (button, card, chip, badge, avatar,
  eyebrow, stat, section) — token-bound, dark- and RTL-aware.
- **RTL**: `<html dir>` comes from the locale; headings swap to the Arabic face via `--font-head`, and
  logical utilities (`ms-*`, `pe-*`, `text-start`, …) mirror the layout for `/ar`.
- **Public talent profile** (`GET /{slug}` → `TalentProfileController` → `talent/profile.blade.php`):
  renders the hero/identity header, then `profile_blocks` in `position` order, each block dispatched to
  a `talent/blocks/{key}` partial (gallery, comp_card, services, reviews, brand_collabs, looks,
  digitals, showreel, equipment, …) reading from eager-loaded relations. Missing media falls back to
  tasteful gradient/initials placeholders.
- `resources/js/http.js` is the shared fetch wrapper: it attaches CSRF + `X-Requested-With` +
  `Accept: application/json`, parses the envelope, and throws `ApiError` (with the field error bag) so
  pages surface validation errors without reloading.

## Domain model — talent side (Phase 1A)

The talent "living creative passport" is a core (`talents`) plus a **malleable block system** and a set
of content tables (`app/Models`, schema in `docs/schema.md`).

- **Professions.** `talents` ⇄ `talent_types` many-to-many via `talent_talent_type` (`is_primary` leads
  the headline, `position` orders). Each `talent_type` carries `default_blocks` (ordered block keys).
- **Block catalog.** `block_types` is the admin-governed catalog. `availability` gates who can add a
  block: `universal` | `by_category` (→ `block_type_category`) | `by_type` (→ `block_type_talent_type`).
  `content_source` says whether the block stores data inline (JSON) or in a rich content table.
- **Layout layer.** `profile_blocks` is the per-talent arrangement (position, visibility, layout, inline
  content). On profile creation the merged+deduped `default_blocks` of all linked types seed the blocks;
  `blockType` is always eager-loaded (rendering needs it). Rich blocks read from the content tables
  (`portfolio_items`, `comp_cards`, `showreels`, `case_studies`, …).
- **Media.** Every model holding uploaded files is `HasMedia` with single-file collections + a `thumb`
  conversion; `*_url` accessors resolve from the library (ADR-5, list in `docs/conventions.md`). External
  links/embeds stay as plain columns.
- **Seeders.** `TalentTypeSeeder` (six professions) + `BlockTypeSeeder` (catalog) are the canonical
  reference data; `TalentDemoSeeder` builds one rich multi-type (model + photographer) talent for later
  phases to render.

> Not yet built: the block-seeding/merge logic will move into an Action + Service in the profile-editor
> phase (currently expressed inline in `TalentDemoSeeder`).

## Talent domain logic — services, actions & state machines (Phase 1B)

Business logic lives in **services** (orchestration + transactions) and single-purpose **actions**;
lifecycles are **state machines**; side effects are **events**.

**Actions** (`app/Actions`, invokable):
- `MergeDefaultBlocksForTypes` — merge + de-dupe the `default_blocks` of a talent's types (ordered).
- `SeedProfileBlocks` — seed `profile_blocks` from the merged defaults (idempotent; each default block
  once), then move the profile `Created → Draft`.

**Services** (`app/Services`, extend `Service`; every multi-write op is `runInTransaction` + fail-logged):
- `ProfileBlockService` — `availableBlockTypes()` (the picker: active + eligible − non-repeatable
  already present), `addBlock`, `fillBlock`, `reorder`, `setVisibility`, `removeBlock`. Rendering
  resolves via `block_type_id → block_types`, so deactivated (grandfathered) blocks still render.
- `ProfessionsService` — `addType` (merges defaults, seeds missing, dedupes), `removeType`,
  `setPrimary`, `reorderTypes`.
- `TalentProfileService` — core fields, hero image, availability, publish/unpublish, rate-card CRUD,
  reviews moderation, affiliations & press.

**State machines** (`app/States`, spatie/laravel-model-states). Each has explicit allowed transitions;
the state is authoritative and a synced boolean/timestamp **projection** (kept by `SyncStateProjections`)
serves the Phase 1A queries/views.

| Machine | Column | States | Projection |
|---|---|---|---|
| TalentProfile | `talents.status` | created → draft → live ⇄ unpublished → suspended/archived | `is_published` + `published_at` (via guarded `ToLive`) |
| Availability | `talents.availability_status` | available ⇄ booked ⇄ unavailable | — |
| Block | `profile_blocks.status` | visible ⇄ hidden | `is_visible` |
| Review | `reviews.status` | pending → approved \| rejected | `is_approved` |
| Service | `services.status` | active ⇄ paused | `is_active` |
| Affiliation | `agency_affiliations.status` | current → past | `is_current` |
| PortfolioMedia | `portfolio_items.status` | uploaded → processed → ordered → visible → archived | — |

**Events / listeners** (auto-discovered in `app/Listeners` by handle() type-hint):
- `TalentProfileViewed` → `IncrementProfileViewCount` (bumps `view_count`; controller dispatches it).
- spatie `StateChanged` → `SyncStateProjections` (projections + published_at stamp).
- medialibrary `MediaHasBeenAddedEvent` → `LogMediaUploaded` (conversions auto-queue).
- medialibrary `ConversionHasBeenCompletedEvent` → `AdvancePortfolioMediaState` (uploaded → processed).

**Policies** (`app/Policies`, auto-discovered): a talent may only manage its own resources
(`TalentPolicy`, `ProfileBlockPolicy`, `ServicePolicy`, `ReviewPolicy`, `AgencyAffiliationPolicy`,
`PortfolioItemPolicy`, `PressFeaturePolicy`), all via `BasePolicy::owns`.

## Talent dashboard (Phase 1C)

The authenticated talent-guard dashboard (`routes/talent.php`, controllers in
`app/Http/Controllers/Talent`, all extending `TalentController`). It is Blade shells + Alpine driven by
the shared `http.js` wrapper — pages render a shell, every interaction is Ajax against JSON-envelope
endpoints, nothing reloads.

- **Thin controllers → services.** Controllers validate (Form Requests in `app/Http/Requests/Talent`),
  call the Phase 1B services (ProfileBlockService / ProfessionsService / TalentProfileService), and
  return Resources (`app/Http/Resources`) wrapped in the envelope. `TalentController::ensureOwns()`
  enforces own-resource access (403); `BlockContentController` resolves the model then `ensureOwns`.
- **Pages.** Home (stats + deals slot), Profile editor (core fields + reorderable blocks + eligibility
  picker + hero upload), Professions, Block content editors (a registry-driven controller serving every
  "table" block — gallery/digitals/showreel/equipment/case-studies/software/brand-collabs/looks — with
  medialibrary upload), Rate card, Availability, Reviews moderation, Affiliations & press, Account.
- **Front-end** (`resources/js/dashboard.js`, Alpine): `profileEditor` (optimistic drag-reorder,
  inline errors, hero upload), `professionsManager`, and a generic `crudList` (paginated load,
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
  stays presentational. Case-study blocks link to their detail page; the header carries a review CTA.
- **Case study** (`CaseStudyController` → `public/case-study`) — one `case_studies` record expanded,
  404 unless it belongs to the (published) talent.
- **Review submission** (`PublicReviewController` + `StoreReviewRequest` → `public/review`) — an Ajax
  form that writes a pending review; the talent moderates it from the dashboard queue.
- **Discovery** (`DiscoveryController` → `public/discover`, `talentSearch` Alpine) — a Blade shell whose
  results come from an Ajax endpoint backed by **`App\Queries\TalentSearch`**, a query object over
  spatie/laravel-query-builder. Filters (type/category through the pivot, availability, city, country,
  equipment, software, free-text) map to `filter[...]` params; results are paginated, eager-loaded
  (`talentTypes` + `media`), and shaped by `TalentCardResource`. Backed by the Phase 1C search indexes
  (see docs/schema.md).

## Deal engine (Phase 1E — shared infrastructure)

The configurable brand ↔ talent deal loop. Admin-authored flows (`deal_flows` +
`deal_flow_steps`) are **snapshotted** into a deal's `deal_steps` at creation (ADR-4), so template
edits never affect in-flight deals. Brand (Phase 2) and Admin (Phase 3) extend the same engine.

**Strategy + Factory — one handler per `step_type`** (`app/Deals/Steps`, resolved by
`App\Deals\StepHandlerFactory`). Each handler `validate()`s the actor's input, `apply()`s side effects
to the deal, declares `isAutomatic()`, and returns a `summary()` for the timeline:

| step_type | handler | behaviour |
|---|---|---|
| form | FormStepHandler | validates `fields`; an `amount_field` sets `deal.agreed_amount` |
| approval | ApprovalStepHandler | approve → advance; reject → loop back (separate path) |
| upload | UploadStepHandler | requires ≥1 `attachments` reference |
| payment | PaymentStepHandler | **ADR-B**: `settings.confirmation` = manual \| auto (default **manual**); auto (or system actor) auto-completes |
| contract | ContractStepHandler | records a signature |
| message | MessageStepHandler | requires a body; echoes it into the thread |
| schedule | ScheduleStepHandler | writes `start_date`/`end_date` onto the deal |
| info | InfoStepHandler | system-actor → auto-complete; human → acknowledge |

**Actions** (`app/Actions/Deals`, single-purpose invokables): `SnapshotDealFlowSteps`, `InitiateDeal`
(create + snapshot + activate first), `AdvanceDeal` (validate/apply → complete → advance), `RejectStep`
(loop back — reopen the disputed step, reset the tail to pending), `ConvertEnquiryToDeal`.

**DealProgression** (`app/Deals`) is the engine the actions share. It holds the invariants: exactly one
step active/awaiting_action at a time, `deal.status` mirrors the current step's actor
(awaiting_brand ⇄ awaiting_talent ⇄ awaiting_admin), automatic steps complete themselves and recurse,
every completion posts a `system_event`, and running out of pending steps completes the deal.

**State machines** (`app/States/Deal|DealStep|DealMessage`, spatie/laravel-model-states):
- **Deal**: draft → awaiting_* (interchangeable) → completed; terminal cancelled/declined/expired; soft-delete.
- **DealStep**: pending → active → awaiting_action → completed; side exits skipped/rejected; reject-loop
  uses completed→rejected→awaiting_action (redo) and completed/awaiting_action→pending (tail reset).
- **DealMessage**: sent → read (`read_at` is the projection); system_event/action_summary are immutable
  (never marked read).

**DealService** (`app/Services/DealService`, `deals` log channel) is the single façade controllers call:
`initiate`, `advance`, `reject`, `skip`, `convertEnquiry`, `postMessage`, `markThreadRead` — each wraps
its action(s) in a transaction with fail-logging.

**Booking CTA / deal initiation**: the public profile Contact button → `EnquiryController` writes a
`deal_enquiries` row (availability-checked, no login); it converts to a deal after the visitor
authenticates as a brand (Phase 2). Talent deal UI: `Talent\DealController` (deal room + inbox) acts as
the `talent` role; the Alpine `dealRoom`/`dealsInbox` components (`resources/js/deals.js`) render a
turn-aware action panel by `step_type` and the interleaved message/system_event timeline.

> **Brands stub**: `deals.brand_id` needs a `brands` table, which is Phase 1B. Phase 1E ships a
> **minimal** brands table (auth surface + name/slug + `is_complete` gate) so the engine references and
> tests can seed brands; Phase 1B adds the full brand core (see docs/schema.md).

## Cross-cutting

- **Logging:** dedicated channels `app`, `auth`, `deals`, `media` (`config/logging.php`). Failure
  convention: catch → log to channel with context → rethrow / return error envelope
  (`Service::runInTransaction`).
- **Transactions:** multi-write operations run through `Service::runInTransaction()` /
  `DB::transaction()`.
- **Strict models:** `preventLazyLoading` + `preventSilentlyDiscardingAttributes` in non-production
  (`AppServiceProvider`).
- **Audit:** `spatie/laravel-activitylog` (table migrated) for admin edits/moderation/overrides.

See `CLAUDE.md` for the full pattern map (which pattern/package backs which part of the domain).
