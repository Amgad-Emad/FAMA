# Fama

Fama is a **creative talent marketplace** — Egypt-first, MENA-wide. It connects **talent** (models,
photographers, cinematographers/DOPs, stylists, creative directors, graphic designers) with **brands**
through rich, malleable talent profiles, a discovery feed, and an admin-configurable **contract engine**
that walks a brand and a talent through booking, quoting, contracting, payment, delivery, and reviews.

> **Canonical reference:** [`docs/specs/`](docs/specs/) is the single source of truth for the data
> model, pages, workflows, and lifecycles. Read the relevant spec before building anything. Project
> laws live in [`CLAUDE.md`](CLAUDE.md); architecture/decisions in [`docs/`](docs/).

## Status

**Talent, brand and admin slices — complete (production-grade).** Full Pest suite **345/345 green**;
`php artisan migrate:fresh --seed` seeds demo data end-to-end. Blade + Alpine on the shared `http.js`
(no page reloads), JSON envelope, i18n (EN/AR + RTL), light/dark. Manual QA checklist:
[`docs/conventions.md`](docs/conventions.md#qa-checklist--talent-slice-manual).

- **Talent** — public pages (profile, project detail, review, enquiry, discovery/search) and the
  dashboard (**Home · Profile · Content · Reviews · Contracts**), where the **Profile editor** is the
  single profile surface (identity, Skills, Username, publish, pricing rate, blocks).
- **Brand** — core + satellites, **projects**, the brand contract room, brand discovery, plus the
  talent-facing brand/opportunity pages and the apply flow (rich-text brief with @-mentions +
  attachments, sanitized server-side).
- **Admin** (`admin` guard + spatie/laravel-permission RBAC) — flow builder, moderation queues (incl. a
  **global review queue** across talent + brand reviews), projects oversight, the **contract intervention
  console**, activity log, settings, and admin users, all reachable from a permission-gated sidebar +
  dashboard cards. **Verification is a hard go-live gate (ADR-U):** no talent or brand reaches the public
  until an admin **Verifies** it (`is_verified` guards the publish transitions); moderators can also
  remove a review with an emailed reason notice (live-preview composer). **Block governance is split (ADR-T):** the **Block catalog** (`/admin/blocks`,
  `manage-blocks`) owns which blocks exist and who may use them (eligibility + grandfathering); the
  **Skills templates** page (`/admin/skills`) only picks each skill's preselected blocks and their order
  from the catalog-eligible set.
- **Contract engine** — admin-authored flows → steps, StepHandler strategy, state machines. Contracts
  **snapshot** their steps at creation, so editing a flow only affects future contracts.

> **Naming:** two repo-wide renames are done — **Campaign → Project** and **Deal → Contract**. Two things
> intentionally keep the old word: the `talent_types.category` enum (`model|crew|creative`) and the
> `project_types` enum value `campaign_video`. `docs/changelog.md` is dated history and still says
> "deal" in entries before 2026-07-15.

> **Removed (`docs/decisions.md` ADR-K/L/M):** the rate-card / services, availability & travel, and
> affiliations & press talent features were removed entirely. Contract amount comes from the flow's
> form/quote step; enquiries are no longer gated by availability.
>
> **Skills + editor consolidation + Pricing rate (ADR-N):** "Professions" is renamed **Skills** across
> the UI/routes (the `talent_types` table is the Skills catalog); the Professions + Account tabs were
> folded into the Profile editor; the public `slug` is shown as **Username**; a new indicative
> **Pricing rate** (`rate_unit`/`rate_amount`/`rate_currency`) replaces the rate card.
>
> **Instagram-style profile header (ADR-O):** the public profile leads with an avatar-based IG-style
> header — **no cover/hero image** (the `hero` collection + `hero_image_url` accessor + editor uploader
> are gone; `avatar` stays). Header = circular avatar + display_name + **@username** + primary-skill
> line + a **Projects · Views · Rating** stats row + bio + optional link + the **Pricing rate** chip +
> Message / Leave-a-review CTAs. Token-only; verified dark + light + RTL.
>
> **Skill-scoped blocks & projects (ADR-Q):** `profile_blocks` and `projects` belong to a skill
> (`talent_type_id`; NULL = profile-level). Adding a skill seeds **its own tab's** blocks (per-skill —
> gallery in both tabs); the picker is per-scope, blocks move between tabs, and removing a skill deletes
> its tab's blocks but preserves content. The editor manages blocks per scope.
>
> **Public profile — two regions (ADR-R):** Region 1 = the IG identity header + universal/meta (location,
> pricing rate, clickable skill chips) + profile-level blocks; Region 2 = **skill tabs** (primary active
> by default, tabs only for skills with visible blocks). The active tab renders server-side; others
> **lazy-load** on click (`GET /{slug}/tab/{skill}`, no reload) and cache; the active tab is deep-linked
> in the URL (`?skill=`, shareable + back button). Projects in a tab are scoped to that skill.
>
> **Skills named as disciplines (ADR-S):** the six Skills read as the **discipline/activity** — Modeling,
> Photography, Cinematography, Creative Direction, Styling, Graphic Design (slugs `modeling` /
> `photography` / `cinematography` / `creative-direction` / `styling` / `graphic-design`), not person-nouns.
> `talent_types` IDs are unchanged so all FKs are intact; the `category` enum (`model/crew/creative`) is
> unchanged (only its display labels are Modeling/Crew/Creative). Old `?skill=` deep links break (no
> redirects). **AR names are a first pass — to be confirmed.**

> **Projects (brand side):** one project = **one role = one position** (`brand_projects.talent_type_id`).
> `budget_is_public` defaults to **false** — a private budget is withheld from every non-owning viewer in
> the payload, not merely hidden in the view. A contract links to its project via
> `contracts.brand_project_id` (ADR-F, landed).

**Phase 5 complete — the public Feed** (spec: `docs/specs/feed-spec.md`; rulings ADR-F1…F8;
performance contract: `docs/performance.md`; design: `docs/design-system.md`): talents publish
notes, media sets and Project shares; talents + brands follow, like, comment and reply (two-level
threads with tombstones); guests read everything and any write is captured, replayed after login,
and returned to the exact post anchor. Two-tier seeded ranking with provably-stable cached-ID
pagination, casting strips, filtered modes (`?skill=`, `?following=1`), mention/link parsing with
bidi-safe rendering and SSRF-guarded enrichment, aggregated database notifications, the feed-scoped
**Daylight** design system (notched frames, blue shadows, editorial credits), a moderation console
with an auto-hide threshold, and an `/api/v1` Sanctum mirror documented by Scribe
(**190 endpoints**, 45 domain tables + the notifications table). A revision batch (5S–5Y) re-grounded
the palette in the topbar colour with a test-enforced WCAG contrast gate, unified the mention
autocomplete, made chips fully replace raw URLs (inline SVG glyph sprite), rebuilt Project spreads
as full previews, added private **Saved/Liked** collections (bookmarks, ADR-F9) and the feed's
motion layer (entrances, the light response, condensing chrome) — 4 more endpoints + the
`bookmarks` table. The **feed rails** (5Z–5AA) followed: a tiered, reason-carrying follow-suggestion
engine (dismissals, truthful bilingual reason lines, per-actor caching) feeding two sidebars —
portrait TALENT TO FOLLOW inline-start, square BRANDS TO FOLLOW inline-end — with talent→brand
following enabled (`brands.followers_count`), optimistic follow/dismiss with undo, guest gating
with intent replay, and responsive fallbacks to inline strips (**200 endpoints** total).
**Round 3** (5AB–5AI) finished the interaction surface: a single z-index scale with a fixed
opaque topbar (Feed-first nav, scroll hide/reveal), floating New-post/back-to-top actions, a
rebuilt action bar with a share popover and overflow menu, generic media-grid geometry with a
teleported lightbox and per-image ALT text, a seven-reason config-driven report flow with honest
confirmations and a toast rack, a reworked comment thread (collapsed composer, optimistic
pending rows with retry), the mention autocomplete portalled above every clipping container
(browser-verified), and a rebuilt New-post modal (pinned chrome, real-grid media preview,
publish hints, dirty-close confirm) — plus a `window.famaLang` bridge so every runtime string
localizes, and a full Arabic pass over the feed's UI copy. A **dashboard follow-management** surface
(FOLLOWING / FOLLOWERS sub-tabs on both the talent and brand dashboards) then turned the follow graph
into a pruning tool: mixed-type cards shaped by type (portrait talents, square brands), a denormalized
`talents.last_posted_at` powering a "quiet since" signal and a least-active sort, a mutual "follows
you back" badge, server-side search / type / Skill filters, optimistic unfollow with in-place undo,
and a strict privacy boundary keeping the dashboard-only signals out of the public listing.

**Deferred — Phase 4:** the full mobile API (Sanctum tokens + Scribe docs) over the existing
services/resources (the feed's `/api/v1` mirror lands with Phase 5, Prompt 5K); and wiring the public
profile's interim **Message** CTA (ADR-P) into the real brand→talent messaging / contract-initiation
flow it reserves.

## Stack

- **PHP 8.3+ / Laravel 13**
- **Auth:** Laravel Breeze (Blade + Alpine + Tailwind + Vite); multi-guard; **Sanctum** for the mobile API
- **Front-end:** Blade + Alpine, **Tailwind v4** (Vite), dark mode (class strategy), full RTL
- **i18n:** `mcamara/laravel-localization` (locale-prefixed routes) + `spatie/laravel-translatable`
- **Domain packages:** `spatie/laravel-medialibrary`, `laravel-model-states`, `laravel-data`,
  `laravel-activitylog`, `laravel-query-builder`
- **API docs:** `knuckleswtf/scribe` · **Tests:** Pest
- **DB:** MySQL (dev database `fama`, test database `fama_test`); any Laravel-supported driver works

## Setup

```bash
composer install
cp .env.example .env          # if you don't have a .env yet; set the MySQL DB_* values
php artisan key:generate
mysql -u root -e "CREATE DATABASE IF NOT EXISTS fama; CREATE DATABASE IF NOT EXISTS fama_test;"
php artisan migrate           # dev DB (fama)
php artisan db:seed           # optional: catalogs + demo talent
npm install
npm run build                 # or `npm run dev` while developing
```

## Run

```bash
composer dev                   # serve + queue + logs + vite (all-in-one)
# or individually:
php artisan serve
npm run dev
php artisan test               # Pest suite — runs on MySQL (fama_test, per phpunit.xml)
```

## Three-guard auth model

Fama has **three login entities**, each with its own session guard + Eloquent provider
([`config/auth.php`](config/auth.php)):

| Guard | Provider → Model | Table | Web home |
|---|---|---|---|
| `admin` (default) | `users` → `App\Models\User` | `users` (migrated) | `/admin/dashboard` |
| `brand` | `brands` → `App\Models\Brand` | `brands` + satellites & projects (Phase 2A–2C) | `/brand/dashboard` |
| `talent` | `talents` → `App\Models\Talent` | `talents` (Phase 1A) | `/talent/dashboard` |

- **Login** is a single, role-aware form: the submitted `role` selects the guard; absent `role`
  defaults to `admin` (the only migrated auth table in Phase 0). `route('dashboard')` dispatches to the
  active guard's dashboard.
- **Dashboards** are guarded route groups (`auth:admin` / `auth:brand` / `auth:talent`). Public pages
  (home now; public talent/brand profiles in Phase 1) are unguarded.
- **Mobile API** authenticates with **Sanctum** tokens (Phase 4); all three models use `HasApiTokens`.

## Folder structure (Fama additions)

```
app/
  Actions/Contracts/Action.php     # the single-purpose action *interface* (Laravel's Contracts convention)
  Actions/Contracting/…            # snapshot / initiate / advance / reject / convert
  Contracting/                     # contract engine: StepHandler strategy + factory + progression
                                   #   (NOT App\Contracts — that namespace is for interfaces)
  States/{Contract,ContractStep,ContractMessage,ContractFlow}/…  # contract lifecycle state machines
  Data/BaseData.php                # base DTO (spatie/laravel-data)
  Enums/UserRole.php               # role ⇄ guard source of truth
  Http/
    Controllers/Auth/…             # guard-aware Breeze auth
    Requests/Auth/LoginRequest.php # role-aware login
    Resources/BaseResource.php     # base API/web resource
  Models/{User,Brand,Talent}.php   # the three login entities
  Policies/BasePolicy.php          # authorization convention
  Providers/AppServiceProvider.php # response macros + strict models
  Services/Service.php             # base service (transactions + fail logging)
  Support/
    ApiResponse.php                # the JSON envelope
    Auth/Guards.php                # multi-guard helper
config/    auth · logging · sanctum · media-library · laravellocalization · scribe · …
resources/
  css/app.css                      # Tailwind v4 entry (dark = class strategy)
  js/http.js                       # shared fetch wrapper (parses the envelope)
  views/…                          # Blade layouts, components, auth
routes/    web.php (locale group + guards) · auth.php · console.php
docs/
  specs/                           # ← canonical: schema-master, talent-spec, brand-spec
  architecture · schema · api · conventions · decisions · changelog
```

## Response envelope

Every JSON response (web-Ajax and API) uses one shape:

```json
{ "success": true, "data": {}, "message": null, "errors": null, "meta": null }
```

Built via `App\Support\ApiResponse` and the `response()->success|error|paginated()` macros. See
[`docs/api.md`](docs/api.md).

## Project laws

- Thin controllers, business logic in services + actions, SOLID throughout.
- All media through the media library; external links stay plain URLs.
- Every interaction is Ajax (no page reloads); lists eager-loaded and paginated.
- **Never** run git write operations. See [`CLAUDE.md`](CLAUDE.md) for the full ruleset.
