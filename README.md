# Fama

Fama is a **creative talent marketplace** — Egypt-first, MENA-wide. It connects **talent** (models,
photographers, cinematographers/DOPs, stylists, creative directors, graphic designers) with **brands**
through rich, malleable talent profiles, a discovery feed, and an admin-configurable **deal engine**
that walks a brand and a talent through booking, quoting, contracting, payment, delivery, and reviews.

> **Canonical reference:** [`docs/specs/`](docs/specs/) is the single source of truth for the data
> model, pages, workflows, and lifecycles. Read the relevant spec before building anything. Project
> laws live in [`CLAUDE.md`](CLAUDE.md); architecture/decisions in [`docs/`](docs/).

## Stack

- **PHP 8.3+ / Laravel 13**
- **Auth:** Laravel Breeze (Blade + Alpine + Tailwind + Vite); multi-guard; **Sanctum** for the mobile API
- **Front-end:** Blade + Alpine, **Tailwind v4** (Vite), dark mode (class strategy), full RTL
- **i18n:** `mcamara/laravel-localization` (locale-prefixed routes) + `spatie/laravel-translatable`
- **Domain packages:** `spatie/laravel-medialibrary`, `laravel-model-states`, `laravel-data`,
  `laravel-activitylog`, `laravel-query-builder`
- **API docs:** `knuckleswtf/scribe` · **Tests:** Pest
- **DB:** SQLite by default (dev + tests); any Laravel-supported driver in production

## Setup

```bash
composer install
cp .env.example .env          # if you don't have a .env yet
php artisan key:generate
touch database/database.sqlite # default DB (or configure another in .env)
php artisan migrate            # Phase 0: vendor/auth + infrastructure tables only
npm install
npm run build                  # or `npm run dev` while developing
```

## Run

```bash
composer dev                   # serve + queue + logs + vite (all-in-one)
# or individually:
php artisan serve
npm run dev
php artisan test               # Pest suite (in-memory SQLite)
```

## Three-guard auth model

Fama has **three login entities**, each with its own session guard + Eloquent provider
([`config/auth.php`](config/auth.php)):

| Guard | Provider → Model | Table | Web home |
|---|---|---|---|
| `admin` (default) | `users` → `App\Models\User` | `users` (migrated) | `/admin/dashboard` |
| `brand` | `brands` → `App\Models\Brand` | `brands` (Phase 1) | `/brand/dashboard` |
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
