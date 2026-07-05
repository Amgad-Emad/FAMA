# Fama — Project Rules (CLAUDE.md)

These are the laws every session obeys.

## Standing rules
- **Persona:** Act as a senior Laravel engineer. Make deliberate, defensible decisions; flag
  trade-offs; never leave silent TODOs.
- **Architecture:** SOLID throughout. Business logic lives in services (and single-purpose Action
  classes). Controllers are thin and only orchestrate; every controller action returns a Response
  (JSON envelope for web Ajax and for API).
- **Patterns:** Use the patterns in the map below — chosen for this domain, not bolted on.
- **Media:** All media goes through spatie/laravel-medialibrary. Uploaded files use media collections
  + conversions (thumbnails are conversions, not manual columns). External links/embeds (YouTube/Vimeo
  URLs, social URLs, brand-collab/press links) stay as plain columns. URL columns for uploaded assets
  are replaced by accessors that resolve from the media library.
- **Auth:** Laravel Breeze (Blade + Alpine + Tailwind + Vite). Three login entities, three guards:
  admin → users, brand → brands, talent → talents. Sanctum issues tokens for the mobile API.
- **i18n:** mcamara/laravel-localization for locale-prefixed routes (/en, /ar) and switching;
  spatie/laravel-translatable for translatable model attributes. Full RTL support in the UI.
- **Front-end:** Blade only. Pages render a shell; every interaction is Ajax (Alpine + a shared fetch
  wrapper) so no page ever reloads. Lists are always eager-loaded and always paginated. Use Laravel
  best practices (Form Requests, Resources/DTOs, scopes, policies).
- **Quality gates:** Required unit + feature tests, dedicated fail-log channels, DB transactions on
  multi-write ops.
- **Docs discipline:** docs/ is a fixed set of files, updated in place to reflect current code — not an
  append-only pile. Update after every prompt.
- **Code comments:** A descriptive docblock above every function explaining what it does (and
  non-obvious why).
- **Git:** Never commit or push. No git write operations under any circumstance.
- **README:** Keep README.md complete and current.

## Canonical reference
Before building anything, check docs/specs/ (schema-master.md, talent-spec.md, brand-spec.md) — the
single source of truth for the data model, pages, workflows, and lifecycles. Architecture decisions
(and open questions) live in docs/decisions.md.

## Pattern map (the "why")
| Concern | Pattern / Package | Where it's used in Fama |
|---|---|---|
| Lifecycles & state transitions | State machine — spatie/laravel-model-states | deal, deal_step, deal_message, campaign, brand, talent profile, availability, review, service, affiliation, media |
| Configurable deal steps | Strategy + Factory — a StepHandler per step_type | the deal-flow engine: form / approval / upload / payment / contract / message / schedule / info |
| Discrete operations | Action classes (single-purpose, invokable) | seed/merge profile blocks, snapshot flow steps, initiate/advance deal, convert enquiry→deal, recalc credibility |
| Orchestration, transactions, logging | Service layer | every domain area; web + API controllers both call the same services |
| Decoupled side effects | Events / Listeners / Observers | deal completed → open review window + bump credibility + advance campaign; media uploaded → conversions; profile view → brand_signals |
| Authorization | Policies | own-resource edits; admin intervention/override |
| Typed input/output | DTOs — spatie/laravel-data | Form Request → Service → Resource, shared between web and API |
| Complex reads | Query objects / scopes + spatie/laravel-query-builder | discovery feed, deals inbox filters, talent search |
| Files | spatie/laravel-medialibrary | all uploads (collections + conversions) |
| i18n | mcamara/laravel-localization + spatie/laravel-translatable | locale routing + translatable attributes |
| Audit | spatie/laravel-activitylog | admin edits, moderation, deal-step overrides |
| Mobile auth | laravel/sanctum | token auth for talents, brands, admins |
| API docs | Scribe (knuckleswtf/scribe) | OpenAPI + Postman collection for the mobile developer |

## Per-prompt ritual (Definition of Done)
Every phase ends with: (1) tests green; (2) fail-logs verified; (3) DB transactions verified;
(4) docs/ updated in place (architecture, schema, api, conventions + a dated changelog line);
(5) this "Current project state" section updated; (6) README current; (7) NO git.

## Current project state
Phase 0 complete: app + packages installed; three auth guards (admin/brand/talent) via Breeze; Sanctum
ready; i18n (mcamara + translatable) + RTL + Tailwind dark mode; shared JSON envelope, log channels,
transaction pattern, preventLazyLoading, and http.js fetch wrapper in place; docs/ + README skeletons
written; docs/specs/ installed as canonical.
Decisions are logged in docs/decisions.md as an ADR log (10 accepted; open items A–E plus retained
F–G need owners) — read it before building, and honor the accepted ADRs.
Phase 1A complete: talent side + malleable block system — migrations, 18 Eloquent models (HasMedia +
translatable + relations), factories, catalog seeders (6 professions + block catalog) and a multi-type
TalentDemoSeeder; media via medialibrary accessors; translatable content fields (list in
docs/conventions.md); tests green. Dev and tests both run on MySQL (tests use a dedicated
`fama_test` database via phpunit.xml + RefreshDatabase).
Front-end foundation: adopted the Fama design system (public/fama-front) — design tokens (light + dark
via `data-theme`) mapped into Tailwind `@theme`, Bodoni Moda + IBM Plex fonts, base UI components
(components/ui/*), and a live public Talent Profile at `GET /{slug}` bound to the demo talent
(light/dark/RTL verified). 69 tests green.
Next: Phase 1B — brand & satellites schema + models (then the deal engine).
