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
ALL PHASES COMPLETE — talent + brand + admin (web) + the versioned mobile API (auth, talent, brand, admin-lite, cross-cutting, handoff) + brand↔talent deal initiation; full Pest suite green. The dated log below is the build history.
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
brand profile GET /brands/{slug} (published-only) and campaign detail GET /brands/{slug}/campaigns/
{campaign-slug} (public-only; nested binding scoped to the brand). Registered before the /{slug} catch-all.

BRAND PHASE COMPLETE (production-grade). Full Pest suite green (222 tests); preventLazyLoading +
preventSilentlyDiscardingAttributes on; N+1 audit clean — media eager-loaded wherever an accessor renders
in a list/loop, proven by query-count tests (flat as campaigns/images/talents grow); every list paginated
+ eager-loaded; every multi-write op in runInTransaction with fail-logging to the brands channel
(rollback + fail-log verified); state machines (Brand/Campaign/BrandReview) with synced projections;
credibility accrues via the DealCompleted event; all colours/fonts are CSS tokens (cloud/graphite/teal +
Bricolage/Sora), dark+light+RTL verified on every brand page (token-only, dir-aware, logical props);
every dashboard interaction is Ajax (no reload). Demo data: fully onboarded Nomad Coffee brand (aesthetic/
needs/credibility/reviews/images + social), two campaigns at different statuses (open + completed
showcase), and a deal under a campaign (deals.campaign_id). Manual QA checklist in docs/conventions.md
("QA checklist — brand slice").
Phase 3A foundation complete (admin platform layer): users refined for admin (locale en/ar, phone,
avatar_url, last_login_at, is_active, soft deletes); Admin RBAC via spatie/laravel-permission on the admin
guard (ADR-H) — roles super-admin/moderator/support + permissions manage-flows/moderate-content/
intervene-deals/manage-settings/manage-users (RolesAndPermissionsSeeder); settings table (key→JSON) +
SettingsService (cached, typed globals, admin log channel) + SettingsSeeder; activity_log confirmed
recording subject/causer/changes (DealFlow/DealFlowStep now LogsActivity, log name deal_flow; this
activitylog version stores model old/new under attribute_changes). 237 tests green.
Phase 3A domain logic complete (admin governance services): AdminService base (admin channel + policy/
permission gating + activity-log record with admin causer). DealFlowBuilderService + DealFlowState machine
(draft→active→archived, is_active synced, is_default unique per applies_to); edits affect future deals only
(snapshot isolation). Moderation services (TalentModerationService, BrandModerationService,
ReviewModerationService incl. batch + brand reviews, CampaignOversightService). ProfessionCatalogService
(default_blocks + add professions), MediaOversightService (retry conversions), DealInterventionService
(advance-as-admin / override stuck step / nudge / reassign / cancel, reusing the 1E engine). Policies for
every capability (auto-discovered, gating spatie permissions on the admin guard). All transactional,
fail-logged, activitylog-audited. 258 tests green.
Phase 3B complete (admin dashboard UI, admin guard, routes/admin.php + app/Http/Controllers/Admin/*):
flow builder (drag-order steps, configure, set default, scope, activate/archive), tabbed moderation queues
(talents/reviews/brands/brand-reviews/campaigns — batch + suspend/verify/cancel), profession/template
manager (visual default_blocks editor + add profession), deal intervention console (override/advance-as-
admin/nudge/reassign/cancel + timeline), searchable activity-log viewer, settings screen, admin-users
management. x-admin-layout + resources/js/admin.js on http.js, JSON envelopes, no reloads. Two-layer authz:
can: middleware gates pages (403 for powerless admin) + the 3A service re-authorizes/audits each action.
268 tests green.

ADMIN PHASE COMPLETE (production-grade: 3A foundation + 3A domain logic + 3B UI). Full Pest suite green
(275 tests); every admin mutation activity-logged with the admin as causer (verified — flow lifecycle via
LogsActivity, moderation/intervention/settings/users explicit); every governance op in runInTransaction
with fail-logging to the admin channel (rollback verified); N+1 clean (moderation queues / deal console /
activity log stay flat, query-count tested); every list paginated + eager-loaded; two-layer authz (can:
middleware gates pages 403 + the 3A service re-authorizes/audits); all colours/fonts are CSS tokens,
dark+light+RTL verified on every admin page (token-only, dir-aware, logical props); every interaction is
Ajax (no reload). Demo data: two extra deal flows (draft + active), pending-moderation items (talent +
brand reviews), and real audit entries (AdminDemoSeeder). Manual QA checklist in docs/conventions.md
("QA checklist — admin slice").
Phase 4A complete (Sanctum mobile API — routes/api.php, /api/v1): stateless token auth for all three
entities via AbstractAuthController + talent/brand/admin controllers (register/login/logout/refresh/me;
ability-scoped tokens — guard name, admin tokens also carry spatie permissions; refresh rotates, logout
revokes; NO public admin signup — admin/register gated by abilities:manage-users, mirroring the web
AdminUserController). Sanctum abilities/ability middleware aliased in bootstrap/app.php; SetApiLocale
negotiates Accept-Language (en/ar, echoes Content-Language) so translatable fields resolve per-locale;
named throttlers api (60/min) + auth (10/min). Read endpoints REUSE existing infra: GET /api/v1/talents
(TalentSearch, paginated) + /talents/{slug} + /brands/{slug} (public) and GET /api/v1/deals[/{deal}]
(auth, scoped to the token's entity). API Resources in app/Http/Resources/Api/V1 (locale-resolved);
existing TalentCardResource/DealResource reused. Central API exception handler shapes 401/403/404/422/429
into the envelope and fail-logs unexpected 5xx to a new `api` log channel. Scribe configured (bearer auth,
grouped/ordered) → /docs + OpenAPI + Postman. ADR-I logged. 291 tests green (+15 in tests/Feature/Api/V1:
auth across all three guards + envelope/pagination/locale/throttle contract); Pint clean; docs updated. NO
git. (ADR-2 honored: web-Ajax and API share the envelope, services, queries and — where shape/locale
matches — Resources; controllers stay thin.)
Phase 4B complete (talent API surface — app/Http/Controllers/Api/V1/Talent/*, routes/api.php under
/api/v1/talent, abilities:talent): the FULL talent management surface as thin controllers over the SAME
services + Form Requests + Resources the web dashboard uses (ADR-2) — profile core + hero upload, profile
blocks CRUD/reorder/eligibility-picker, professions, the 8 content child tables (gallery/look_types/
digitals/showreel/equipment/projects/software_stack/brand_collabs) via a NEW shared
App\Support\Talent\BlockContentRegistry (single source of truth; web BlockContentController refactored to
use it), comp card (1:1 upsert), services/rate-card, availability & travel, reviews moderation,
affiliations, press, account+publish, the deal room (inbox filter/paginate + room steps+messages+whose-turn
+ step actions via DealService) and incoming enquiries. Public interactions added to Api\V1\TalentController:
enriched GET /talents/{slug} (visible blocks + comp card), GET /talents/{slug}/projects/{project} (case
study), public POST /talents/{slug}/reviews (pending) + POST /talents/{slug}/enquiries. Media via
medialibrary multipart (hero + per-content) returning conversion URLs. New TalentApiController base
(talent()=sanctum tokenable, ensureOwns 403). Two slug-uniqueness Form Requests made guard-agnostic
(talent OR sanctum). 339 tests green (+47 in tests/Feature/Api/V1/Talent: auth/ownership/validation/
pagination/media/locale/deal-engine); web content tests still green after the registry extraction; Pint
clean; Scribe regenerated (Talent · … groups); docs/api.md + changelog + CLAUDE updated. NO git.
Phase 4C complete (brand API surface — app/Http/Controllers/Api/V1/Brand/*, routes/api.php under
/api/v1/brand, abilities:brand): the FULL brand management surface as thin controllers over the SAME
services the web dashboard uses — profile core + logo/cover/aesthetic/images/social, the 6-step onboarding
(BrandOnboardingService), creative-needs, campaigns (CRUD + roles + media + status transitions +
single-campaign deals via CampaignService), the personalised discovery feed (BrandTalentFeed) with
save/brief signal writes (BrandSignalService), the brand deal room (inbox filter/paginate + room +
brand-side step actions brief/accept/sign/pay via DealService), reviews received (read-only) and
credibility (read), account settings-stage fields + publish. Public reads in Api\V1\BrandController:
enriched GET /brands/{slug} (Api\V1\BrandResource now carries credibility/aesthetic/social/images/approved
reviews/public campaigns) + GET /brands/{slug}/campaigns/{slug} (scope-bound public campaign). New
Api\V1\CredibilityResource; new shared App\Support\Brand\BrandOptions (single source of truth for brand
enum Rule::in lists). New BrandApiController base (brand()=sanctum tokenable, ensureOwns 403). Media via
medialibrary multipart returning URLs. 381 tests green (+42 in tests/Feature/Api/V1/Brand: auth/ownership/
validation/pagination/discovery-filters/signals/campaign-lifecycle/deal-engine); no web regressions; Pint
clean; Scribe regenerated (Brand · … groups); docs/api.md + changelog + CLAUDE updated. NO git.
The mobile API now covers all three guards end to end (4A auth + 4B talent + 4C brand).
Phase 4D complete (cross-cutting endpoints): (1) public brand DIRECTORY GET /api/v1/brands via new
App\Queries\BrandSearch (spatie/query-builder, whitelisted filters industry/stage/reach/city/country/
verified/q + sorts created_at/name) alongside talent search; unknown filter/sort → 400 envelope (new
InvalidQuery handler in bootstrap/app.php, also closes the same gap on the talent feed). (2) NOTIFICATIONS:
notifications table + App\Notifications\DealTurnChanged/NewDealMessage (database channel) dispatched from
DealService on turn change (advance/reject/skip) + new message; endpoints (ability:talent,brand) list/
unread-count/mark-read/mark-all — basic delivery, stable contract. (3) REFERENCE/LOOKUPS (public) GET
/api/v1/lookups/{talent-types,block-types,deal-flows,options} with locale-resolved Api\V1 lookup resources.
(4) ADMIN-LITE reads (policy-gated) GET /api/v1/admin/overview (abilities:admin) + /admin/activity
(abilities:manage-settings) — heavy admin stays on web. Locale + media consistent throughout. 399 tests
green (+18 in tests/Feature/Api/V1: Search/Notification/Lookup/AdminLite); no regressions from the
DealService notification wiring; Pint clean; Scribe regenerated; docs/api.md + changelog + CLAUDE updated.
NO git.

MOBILE API PHASE COMPLETE (production-grade: 4A auth + 4B talent + 4C brand + 4D cross-cutting + 4E
handoff). 130 versioned /api/v1 endpoints, all reusing the web services/queries/Resources (ADR-2) with
thin controllers. Full Pest suite green (407 tests); a ContractComplianceTest asserts the JSON envelope +
complete meta.pagination on EVERY paginated list (and, running with preventLazyLoading on + seeded data,
proves no endpoint lazy-loads). Unbounded lists all paginate; bounded editor-state (blocks/professions/
images/social) + reference lookups intentionally return whole collections (documented). Consistent error
envelopes for 400/401/403/404/422/429/500 (central handler in bootstrap/app.php incl. spatie InvalidQuery
→ 400). Rate limits: api 60/min + auth 10/min (429 with meta.retry_after). Accept-Language honored on all
public reads — added Api\V1\CampaignResource so public campaign description/caption resolve single-locale
(owner endpoints keep per-locale maps). Scribe regenerated; OpenAPI + Postman exported to docs/api/ via the
new `composer api-docs` script (reproducible). docs/api.md is the mobile handoff index (base URL, per-entity
auth flow, envelope, error table, pagination, locale, rate limits, links to the generated reference +
Postman); README references it; this section marks the phase COMPLETE. NO git.
DEAL INITIATION COMPLETE (the last engine gap): the real brand↔talent entry points, web + /api/v1
(abilities:brand), as thin controllers over the existing DealService — NO engine rebuild. Path A "Start a
deal" (StartDealRequest → DealService::startBrandDeal): CTAs on the discovery card, the brand-authenticated
talent profile, and the campaign workspace (carrying campaign_id); guards (talent published + available,
brand is_complete) → 422; reuses initiate(). Path B enquiry→deal (DealService::convertEnquiry, flow now
optional/resolved): a brand's pending enquiries (email-matched contact_email + status=new) surface as a
list (GET /brand/enquiries + API) + a dashboard count; convert is email-owned (403) + once-only (422),
setting status=converted + converted_deal_id. Flow resolution (DealService::resolveFlowForTalent +
Talent::primaryCategory()): explicit flow must be active + applicable, else category-scoped active default,
else global active default, else 422. App\Notifications\DealStarted (type deal.started) notifies the talent
on initiation/conversion; the deal shows immediately in GET /talent/deals. UI: reusable brandStartDeal
modal (x-brand.start-deal-modal), brandEnquiries list, Enquiries nav + dashboard prompt — Blade+Alpine on
http.js, JSON envelope, no reload, dark/light + RTL, Arabic added (lang/ar.json, 0 missing). 420 tests
green (+13 in tests/Feature/Deals/DealInitiationTest incl. the END-TO-END loop from a UI-initiated deal →
credibility accrual + brand-review window); ContractCompliance extended; every new multi-write via
runInTransaction (deals channel); N+1 clean; Pint clean; Scribe/OpenAPI/Postman regenerated (133
endpoints); docs + ADR-J added (ADR-I read-only line corrected). NO git.
Next: richer notification delivery (push/email channels on the existing contract); brand team/roles
(ADR-A, still open); talent admission gate (ADR-C, still open).
