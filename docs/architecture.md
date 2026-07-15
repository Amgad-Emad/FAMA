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
  (ADR-N).
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
- **Seeders.** `TalentTypeSeeder` (six skills) + `BlockTypeSeeder` (catalog) are the canonical
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
  (deletes the tab's blocks but **preserves content** — items un-linked, projects un-scoped — and logs
  it), `setPrimary`, `reorderTypes`. (Skills manager; renamed from `ProfessionsService` — ADR-N.)
- `TalentProfileService` — core fields, the Pricing rate (`updatePricingRate`, all-or-nothing),
  publish/unpublish, reviews moderation. *(The hero/cover uploader was removed — ADR-O.)*

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
- `TalentProfileViewed` → `IncrementProfileViewCount` (bumps `view_count`; controller dispatches it).
- spatie `StateChanged` → `SyncStateProjections` (projections + published_at stamp).
- medialibrary `MediaHasBeenAddedEvent` → `LogMediaUploaded` (conversions auto-queue).
- medialibrary `ConversionHasBeenCompletedEvent` → `AdvancePortfolioMediaState` (uploaded → processed).

**Policies** (`app/Policies`, auto-discovered): a talent may only manage its own resources
(`TalentPolicy`, `ProfileBlockPolicy`, `ReviewPolicy`, `PortfolioItemPolicy`), all via
`BasePolicy::owns`.

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

The brand core (`brands`, extended from the Phase 1E stub) plus its satellites and campaigns
(`app/Models`, schema in `docs/schema.md`). Schema layer only — services + state machines + the brand
dashboard are Phase 2B/2C (same split as talent 1A → 1B).

- **Identity & gates.** `brands` holds the public identity; onboarding fills it progressively (nullable
  fields). `is_complete` gates transacting, `is_published` gates talent-visibility, `is_verified` is a
  one-way admin trust upgrade. `description` is translatable; logo + cover are medialibrary collections.
- **1:1 satellites.** `aesthetic` (`BrandAesthetic`), `creativeNeed` (`BrandCreativeNeed`), `credibility`
  (`BrandCredibility`) — created once, updated in place.
- **Child collections.** `images` (medialibrary), `brandReviews` (three sub-ratings, `average_rating`
  accessor), `socialHandles`, `signals` (append-only), `campaigns`.
- **ADR-6 discovery pivots.** Mood tags → `brand_mood_tags` (via the aesthetic); creative-need talent
  types → `brand_creative_need_talent_type` (M:N with `talent_types`); project types →
  `brand_creative_need_project_type`. All indexed so the feed can filter "brands needing photographers /
  with an editorial mood / running campaign videos".
- **Campaigns.** `Campaign` (translatable description, cover media, soft-delete, `status` lifecycle)
  `belongsToMany` `TalentType` via `campaign_talent_types` (roles + `quantity`) and `hasMany`
  `CampaignMedia` (`gallery()`, uploads via medialibrary). A campaign groups many contracts (the
  `contracts.campaign_id` FK lands in Phase 2C, ADR-F).
- **Demo data.** `BrandDemoSeeder` builds a full brand (Nomad Coffee) — aesthetic + moods, creative
  needs + pivots, credibility, images, social handles, a talent review, and a public campaign with roles
  + gallery — enriching the same brand the contract seeder uses.

## Brand domain logic — services, state machines & events (Phase 2B)

Business logic in **services**; lifecycles are **state machines**; accrual is **event-driven**. Every
multi-write op runs in `runInTransaction` with fail-logging to the **`brands`** channel.

**State machines** (`app/States/Brand|Campaign|BrandReview`, spatie/laravel-model-states — `status`
authoritative, flags are synced projections via `SyncStateProjections`):
- **Brand**: registered → onboarding → complete → published ⇄ unpublished; suspended from any complete
  state; soft-delete removes it. `is_complete`/`is_published`/`is_active` are projected from status;
  **`is_verified` is orthogonal** — a one-way admin flag, not a status.
- **Campaign**: draft → open → in_progress → completed; cancellable from any active state. `is_public`
  toggles independently of status; `Campaign::showcase()` = completed + public.
- **BrandReview**: pending → approved | rejected (mirrors talent reviews); `is_approved` synced.

**Services** (`app/Services`):
- `BrandOnboardingService` — the 6-step wizard (identity → location → creative needs → aesthetic+images
  → budget → complete). Step 1 moves registered → onboarding; step 6 flips `is_complete`. Idempotent
  per step.
- `CampaignService` — create/edit, `syncRoles` (talent types + quantity), `addMedia`, and the status
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
> of their own; `brand_signals` is append-only. Contracts link to a campaign via `contracts.campaign_id` (ADR-F).

### Brand dashboard (Phase 2C)

The brand-guard dashboard (`routes/brand.php`, `app/Http/Controllers/Brand/*`, `<x-brand-layout>`,
`resources/js/brand.js`) mirrors the talent dashboard: Blade shells + Alpine on `http.js`, JSON envelopes,
no reloads, ownership 403 / domain 422. Controllers are thin and delegate to the Phase 2B services — the
6-step **onboarding wizard** (persists per step, flips `is_complete`, then drops the brand into its first
feed), **profile editor** (core + aesthetic + images + social), **creative-needs** editor, **campaigns**
manager + workspace (roles, media, lifecycle buttons, the contracts running under the campaign), the
**discovery feed** (infinite/paginated via `BrandTalentFeed`, save/brief write signals), the **brand contract
room** (the `brand` side of the shared engine — `awaiting_brand` highlighted, action panel keyed by
`step_type`), read-only **reviews received**, and **account** (settings + publish toggle). An incomplete
brand is redirected into onboarding by the dashboard.

## Cross-cutting

- **Logging:** dedicated channels `app`, `auth`, `contracts`, `media` (`config/logging.php`). Failure
  convention: catch → log to channel with context → rethrow / return error envelope
  (`Service::runInTransaction`).
- **Transactions:** multi-write operations run through `Service::runInTransaction()` /
  `DB::transaction()`.
- **Strict models:** `preventLazyLoading` + `preventSilentlyDiscardingAttributes` in non-production
  (`AppServiceProvider`).
- **Audit:** `spatie/laravel-activitylog` (table migrated) for admin edits/moderation/overrides.

See `CLAUDE.md` for the full pattern map (which pattern/package backs which part of the domain).
