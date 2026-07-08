# Changelog

Notable changes to the Fama project. Newest first.

## 2026-07-09 — Phase 2B: brand domain logic (services, states, accrual)

- **State machines** (status authoritative, flags synced via `SyncStateProjections`): Brand
  (registered→onboarding→complete→published⇄unpublished→suspended; `is_verified` orthogonal one-way),
  Campaign (draft→open→in_progress→completed; cancellable; `is_public` independent; `showcase()` scope),
  BrandReview (pending→approved/rejected). Migration adds `brands.status` + **`deals.campaign_id`**
  (ADR-F resolved) with `Deal::campaign()`/`Campaign::deals()`.
- **Services** (transactional, `brands` log channel): `BrandOnboardingService` (6-step wizard, flips
  `is_complete`), `CampaignService` (create/edit/roles/media/transitions), `BrandReviewService`
  (submit-pending/approve/reject, no brand edit path), `BrandSignalService` (append-only),
  `BrandCredibilityService`.
- **Credibility accrual (event-driven):** `DealProgression` fires **`DealCompleted`** → auto-discovered
  `AccrueBrandCredibility` listener → `RecalculateBrandCredibility` (monotonic project count, response
  metrics, internal brief score). Brand takes no action.
- **Discovery feed:** `App\Queries\BrandTalentFeed` (spatie/laravel-query-builder) — personalised by the
  brand's creative-need talent types (pivot) + geographic_reach; paginated + eager-loaded; writes a
  browse signal. Aesthetic weighting deferred (documented).
- **Tests +14 (190 green):** onboarding (6 steps + idempotency), credibility accrual (+ monotonic),
  review flow (submit/guard/approve/no-edit), campaign transitions (+ illegal + showcase), discovery
  feed (needs/geo/pagination + signal). Docs (architecture) + ADR-F resolved + CLAUDE updated. No git.

## 2026-07-08 — Phase 2A: brand core & satellites + campaigns (schema)

- **ADR-E resolved:** brand-spec confirmed complete (no gaps) — Phase 2 unblocked.
- **Migrations:** extended the `brands` stub into the full identity (nullable onboarding/settings fields
  + discovery indexes; logo/cover via medialibrary); satellites `brand_aesthetics`, `brand_images`,
  `brand_creative_needs`, `brand_credibility`, `brand_reviews` (three sub-ratings), `brand_social_handles`,
  `brand_signals` (append-only); `campaigns` + `campaign_talent_types` + `campaign_media`.
- **ADR-6 applied (brand side):** `mood_tags` → `brand_mood_tags`; creative-need `talent_types` →
  `brand_creative_need_talent_type` (M:N); `project_types` → `brand_creative_need_project_type` — all
  indexed for discovery. Free-text references + internal `budget_tier`/`brief_quality_score` kept.
- **Models + factories** for all of the above (Brand extended: media, translatable `description`,
  full relations; Campaign: roles-with-quantity pivot + `gallery()`; append-only `BrandSignal`).
- **`BrandDemoSeeder`** — a full demo brand (Nomad Coffee: aesthetic+moods, needs+pivots, credibility,
  images, social handles, a talent review, a public campaign with roles + gallery), with generated
  images, enriching the deal seeder's brand.
- **Tests +8 (176 green):** relationships, translatable, media accessors, both ADR-6 pivots
  (discovery-shaped queries), brand-review average + pending, campaign roles/quantity/gallery,
  append-only signals. Docs (schema/architecture/conventions) + CLAUDE updated. No git.

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
