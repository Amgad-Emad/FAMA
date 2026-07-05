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
