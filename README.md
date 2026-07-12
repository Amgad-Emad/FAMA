# Fama

Fama is a **creative talent marketplace** — Egypt-first, MENA-wide. It connects **talent** (models,
photographers, cinematographers/DOPs, stylists, creative directors, graphic designers) with **brands**
through rich, malleable talent profiles, a discovery feed, and an admin-configurable **deal engine**
that walks a brand and a talent through booking, quoting, contracting, payment, delivery, and reviews.

> **Canonical reference:** [`docs/specs/`](docs/specs/) is the single source of truth for the data
> model, pages, workflows, and lifecycles. Read the relevant spec before building anything. Project
> laws live in [`CLAUDE.md`](CLAUDE.md); architecture/decisions in [`docs/`](docs/).

## Status

**Talent slice — complete (production-grade).** Public pages (profile, project detail, review, enquiry,
discovery/search), the full talent dashboard — sidebar **Home · Profile · Content · Reviews · Deals**,
where the **Profile editor** is the single profile surface (identity, Skills, Username, publish, pricing
rate, blocks) — and the shared **deal engine** (flows → steps →

deals, StepHandler strategy, state machines) with the talent deal room + inbox. Blade + Alpine on the
shared `http.js` (no page reloads), JSON envelope, i18n (EN/AR + RTL), light/dark. Full Pest suite
green; demo data seeded (`php artisan migrate:fresh --seed`). Manual QA checklist:
[`docs/conventions.md`](docs/conventions.md#qa-checklist--talent-slice-manual).

> **Removed (`docs/decisions.md` ADR-K/L/M):** the rate-card / services, availability & travel, and
> affiliations & press talent features were removed entirely. Deal amount comes from the flow's
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

**Next — Phase 2A:** brand core & satellites, then the brand-side deal room (Phase 2C) on the same
engine; Admin authoring/intervention is Phase 3.

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
| `brand` | `brands` → `App\Models\Brand` | `brands` + satellites & campaigns (Phase 2A–2C) | `/brand/dashboard` |
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
  Actions/Contracts/Action.php     # single-purpose action contract
  Actions/Deals/…                  # snapshot / initiate / advance / reject / convert
  Deals/                           # deal engine: StepHandler strategy + factory + progression
  States/{Deal,DealStep,DealMessage}/…  # deal lifecycle state machines
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
