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
discovery/search), the full talent dashboard (profile/blocks, professions, content editors, rate card,
availability, reviews, affiliations & press, account), and the shared **deal engine** (flows → steps →
deals, StepHandler strategy, state machines) with the talent deal room + inbox. QA checklist:
[`docs/conventions.md`](docs/conventions.md#qa-checklist--talent-slice-manual).

**Brand slice — complete (production-grade).** Public brand profile + campaign detail pages; the full
brand dashboard (6-step onboarding wizard, home, profile editor, creative-needs, campaigns manager +
workspace, discovery feed, reviews received, account) and the **brand side of the shared deal engine**
(deal room + inbox, `deals.campaign_id`). Domain logic in services with state machines
(Brand/Campaign/BrandReview) and event-driven credibility accrual. N+1 audit clean (query-count tested);
transactions + fail-logs verified; demo brand (Nomad Coffee) with two campaigns + a deal under a campaign.
QA checklist: [`docs/conventions.md`](docs/conventions.md#qa-checklist--brand-slice-manual).

**Admin slice — complete (production-grade).** Admin foundation (spatie/laravel-permission RBAC on the
admin guard, `settings` + `SettingsService`, activity-log audit), the governance domain services
(deal-flow builder + template state machine, talent/brand/review/campaign moderation, profession/catalog,
media oversight, deal intervention), and the **admin dashboard** (flow builder, moderation queues,
profession manager, deal intervention console, activity-log viewer, settings, admin-user management).
Two-layer authz (`can:` middleware + service re-check); **every mutation is activity-logged**; N+1 clean;
demo flows + pending-moderation items + audit entries seeded. QA checklist:
[`docs/conventions.md`](docs/conventions.md#qa-checklist--admin-slice-manual).

All slices: Blade + Alpine on the shared `http.js` (no page reloads), JSON envelope, i18n (EN/AR + RTL),
light/dark. Full Pest suite green; demo data seeded (`php artisan migrate:fresh --seed`).

**Next — Phase 4A:** the **Sanctum mobile API** (token auth for talents/brands/admins, versioned JSON
endpoints, Scribe docs) — and brand↔talent deal initiation on the shared engine.

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
