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
via `data-theme`) mapped into Tailwind `@theme`, base UI components (components/ui/*), and a live public
Talent Profile at `GET /{slug}` bound to the demo talent (light/dark/RTL verified). Theme: cool cloud
surfaces + graphite ink + teal accent; Bricolage Grotesque (display) + Sora (UI) + IBM Plex Sans Arabic
(RTL headings via dir-aware `--font-head`) + IBM Plex Mono. All colours/fonts are CSS vars in
resources/css/app.css — restyling is token-only.
Talent domain logic complete: block engine (MergeDefaultBlocksForTypes + SeedProfileBlocks actions,
ProfileBlockService with eligible picker), ProfessionsService, TalentProfileService; 7 state machines
(app/States) with a synced-projection convention; auto-discovered events/listeners (view count,
published_at, media pipeline); own-resource policies.
Talent dashboard complete (talent guard, routes/talent.php + app/Http/Controllers/Talent/*): home,
profile editor (reorderable blocks + eligibility picker + hero upload), professions, block content
editors (registry-driven + media), rate card, availability, reviews moderation, affiliations & press,
account. Blade shells + Alpine (resources/js/dashboard.js) on http.js, JSON envelopes, no reloads,
ownership 403 / domain 422.
Public pages complete (unguarded, routes/web.php + app/Http/Controllers/*): talent profile GET /{slug}
(view_count via event, no N+1), case study GET /{slug}/work/{caseStudy}, review submission GET|POST
/{slug}/review (pending), and discovery GET /discover (+ /discover/search) backed by App\Queries\
TalentSearch (spatie/laravel-query-builder) with ADR-6 search indexes (migration add_discovery_search_
indexes).
Deal engine complete (Phase 1E, shared infra — app/Deals, app/Actions/Deals, app/Services/DealService,
app/States/Deal|DealStep|DealMessage): deal_flows/deal_flow_steps/deals/deal_steps/deal_messages/
deal_enquiries + a MINIMAL brands stub table (Phase 1B extends). StepHandler Strategy+Factory (8 types;
PaymentStepHandler manual-vs-auto per ADR-B, default manual); Actions Snapshot/Initiate/Advance/
RejectStep(loop-back)/ConvertEnquiry sharing DealProgression; state machines; DealService in
transactions (deals channel). Booking CTA GET|POST /{slug}/enquire → deal_enquiries. Talent deal room +
inbox (routes/talent.php, resources/js/deals.js).

TALENT PHASE COMPLETE (production-grade). Full Pest suite green; preventLazyLoading +
preventSilentlyDiscardingAttributes on (no N+1); every list paginated + eager-loaded; every multi-write
op wrapped in runInTransaction with fail-logging to the right channel (deals / media / app); all
colours/fonts are CSS tokens (cloud/graphite/teal + Bricolage/Sora), dark+light+RTL verified on every
talent page; every dashboard interaction is Ajax (no reload). Demo data: multi-type demo talent with
full blocks/content/images + three deals at different steps (awaiting_talent / awaiting_brand /
completed), plus 10 showcase talents across all six professions. Manual QA checklist in
docs/conventions.md ("QA checklist — talent slice").
Phase 2A complete (brand schema): ADR-E resolved (brand-spec confirmed complete). brands extended to the
full identity (media logo/cover, translatable description); satellites brand_aesthetics (+brand_mood_tags
pivot), brand_images, brand_creative_needs (+talent_type/project_type pivots), brand_credibility,
brand_reviews (3 sub-ratings), brand_social_handles, brand_signals (append-only); campaigns +
campaign_talent_types + campaign_media. ADR-6 applied brand-side. 12 models + factories, BrandDemoSeeder
(full Nomad Coffee brand + campaign with images). 176 tests green. State machines/services for the brand
side are NOT built yet (schema layer only, mirroring talent 1A→1B).
Phase 2B complete (brand domain logic): state machines Brand/Campaign/BrandReview (status authoritative,
flags synced via SyncStateProjections; is_verified orthogonal; deals.campaign_id added — ADR-F resolved).
Services (brands log channel): BrandOnboardingService (6-step wizard, flips is_complete), CampaignService
(create/edit/roles/media/transitions + showcase scope), BrandReviewService (submit-pending/approve/reject,
no brand edit), BrandSignalService (append-only), BrandCredibilityService. Event-driven accrual:
DealProgression fires DealCompleted → AccrueBrandCredibility listener → RecalculateBrandCredibility
(monotonic). Discovery feed App\Queries\BrandTalentFeed (needs pivot + geographic_reach; aesthetic
weighting deferred). 190 tests green.
Phase 2C complete (brand dashboard UI, brand guard, routes/brand.php + app/Http/Controllers/Brand/*):
6-step onboarding wizard (flips is_complete → first feed), dashboard home, profile editor (core +
aesthetic + mood tags + images + social), creative-needs editor, campaigns manager + workspace (roles +
media + lifecycle + deals under deals.campaign_id), discovery feed (BrandTalentFeed, paginated, save/brief
signals), deals inbox + brand deal room (brand side of the shared engine, awaiting_brand highlighted),
reviews-received (approved-only, read-only), account (settings + publish toggle). x-brand-layout + Alpine
(resources/js/brand.js) on http.js, JSON envelopes, no reloads, ownership 403 / domain 422. 202 tests green.
Public brand pages complete (unguarded, routes/web.php + app/Http/Controllers/BrandProfileController):
brand profile GET /brands/{slug} (published-only; header + credibility + approved reviews + public
campaigns + social/aesthetic, eager-loaded no N+1) and campaign detail GET /brands/{slug}/campaigns/
{campaign-slug} (public-only; description/cover/budget/location/dates + roles sought + gallery; nested
binding scoped to the brand). Registered before the /{slug} talent catch-all. 208 tests green.
Next: Admin (Phase 3 — flow authoring, moderation/approval queues, deal-step intervention/override,
activity log), then brand↔talent deal initiation (brand discovers talent → enquiry→deal on the shared
engine), then the Sanctum mobile API (Phase 4).
deals.campaign_id (ADR-F) lands with campaigns.
