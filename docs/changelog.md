# Changelog

Notable changes to the Fama project. Newest first.

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
