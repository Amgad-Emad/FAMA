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
| Lifecycles & state transitions | State machine — spatie/laravel-model-states | deal, deal_step, deal_message, campaign, brand, talent profile, review, media |
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
Decisions are logged in docs/decisions.md as an ADR log (13 accepted — 1–10 plus K/L/M for the three
removed talent features; open items A–E plus retained F–G need owners) — read it before building, and
honor the accepted ADRs.
Phase 1A complete: talent side + malleable block system — migrations, 18 Eloquent models (HasMedia +
translatable + relations), factories, catalog seeders (6 skills + block catalog) and a multi-type
TalentDemoSeeder; media via medialibrary accessors; translatable content fields (list in
docs/conventions.md); tests green. Dev and tests both run on MySQL (tests use a dedicated
`fama_test` database via phpunit.xml + RefreshDatabase).
Front-end foundation: adopted the Fama design system (public/fama-front) — design tokens (light + dark
via `data-theme`) mapped into Tailwind `@theme`, base UI components (components/ui/*), and a live public
Talent Profile at `GET /{slug}` bound to the demo talent (light/dark/RTL verified). Theme: cool cloud
surfaces + graphite ink + teal accent; Bricolage Grotesque (display) + Sora (UI) + IBM Plex Sans Arabic
(RTL headings via dir-aware `--font-head`) + IBM Plex Mono. All colours/fonts are CSS vars in
resources/css/app.css — restyling is token-only.
Talent domain logic complete: block engine (SeedBlocksForSkill action + scope-aware ProfileBlockService),
SkillsService, TalentProfileService; 4 state machines
(app/States: TalentProfile, Block, Review, PortfolioMedia) with a synced-projection convention;
auto-discovered events/listeners (view count, published_at, media pipeline); own-resource policies.
Blocks & projects are skill-scoped (2026-07-11, ADR-Q): profile_blocks.talent_type_id + projects.
talent_type_id (nullable; NULL = profile-level/universal, NOT NULL = that skill's tab; position is
per-scope). Seeding is per-skill (adding a skill seeds ITS default_blocks into its own tab; dedupe scoped
to (talent,skill,block_type) — gallery appears in both tabs); is_repeatable now means once-per-scope; the
picker is per-scope; blocks can move between scopes (PATCH /talent/profile/blocks/{block}/move); removing
a skill deletes its tab blocks but preserves content (items un-linked, projects un-scoped). Migration
2026_07_11_000200 backfills.
Public profile is two regions (2026-07-11, ADR-R): Region 1 = identity + universal/meta (location, pricing
rate) + profile-level blocks (talent_type_id NULL); Region 2 = skill tabs — primary
active by default, tabs only for skills WITH visible blocks (block-less skill → no tab; single skill → no
bar). The active tab renders server-side; others lazy-fetch via GET /{slug}/tab/{skill} (envelope {html},
eager, no N+1) cached client-side; active tab in the URL (?skill=slug — shareable + back button via
popstate); view_count bumps once (not per switch). Projects in a tab are scoped to that skill. App
contract = App\Http\Resources\PublicProfileResource (identity + universal_blocks + skills[].blocks[]).
No composer api-docs (mobile API Phase 4).
Skill tabs are the profile's PRIMARY NAV (2026-07-12, presentation-only): the tab bar is a sticky (top-16,
under the site header), horizontally-scrollable pill/segmented control separated from the identity region by
a divider; each tab shows the skill icon (<x-skill-icon> keyed on talent_types.icon), name, and a count badge
(visible blocks). The ACTIVE tab is FILLED (bg-accent + on-accent + font-semibold), not an underline; inactive
legible with hover + focus-visible. Full ARIA: role=tablist/tab/tabpanel, aria-selected, aria-controls/
aria-labelledby, roving tabindex, arrow/Home/End nav (RTL-aware, activation-follows-focus). The panel renders
the active skill's NAME as a heading (context when the bar scrolls out on mobile) and fades on switch
(reduced-motion-aware, via a forced-reflow opacity swap — no rAF). Mobile: overflow-x-auto + snap + edge fades,
never wraps. The header's skill chips were REMOVED (the tab bar is the nav); the primary-skill line stays.
profileTabs (dashboard.js) gains labels + onTabKey + swapPanel; jump() removed. All Prompt-H behaviour intact.
Verified dark/light/RTL in-browser.
Skills named as disciplines (2026-07-11, ADR-S): the six talent_types read as the discipline/activity —
Modeling, Photography, Cinematography, Creative Direction, Styling, Graphic Design (slugs modeling /
photography / cinematography / creative-direction / styling / graphic-design), not person-nouns. Migration
2026_07_11_000300 renames rows in place (no-op on fresh — TalentTypeSeeder seeds the new values); IDs
unchanged → all FKs intact. The category enum (model/crew/creative) is unchanged — only its display labels
are Modeling/Crew/Creative, and a single-chip group whose label duplicates its lone chip (Modeling)
suppresses the header. Old ?skill= deep links break (no redirects). AR names are a first pass — confirm
with stakeholders.
Talent dashboard complete (talent guard, routes/talent.php + app/Http/Controllers/Talent/*). Sidebar:
Home · Profile · Content · Reviews · Deals. The **Profile editor** is the single profile surface —
identity + the **profile image (avatar) uploader** (POST/DELETE /talent/profile/avatar →
TalentProfileService::updateAvatar/removeAvatar → the `avatar` single-file media collection; Ajax preview,
initials fallback; UpdateAvatarRequest validates image/mimes/≤5MB; only the circular avatar, no cover — ADR-O)
+ Username (the `slug`, relabelled), the **Skills** section (SkillController under
/talent/profile/skills*), the **Pricing rate**, the **Publish** toggle (PATCH /talent/profile/publish),
and reorderable blocks + eligibility picker; plus block content editors (registry-driven +
media) and reviews moderation. Blade shells + Alpine (resources/js/dashboard.js) on http.js, JSON
envelopes, no reloads, ownership 403 / domain 422.
Public pages complete (unguarded, routes/web.php + app/Http/Controllers/*): talent profile GET /{slug}
(view_count via event, no N+1), case study GET /{slug}/work/{caseStudy}, review submission GET|POST
/{slug}/review (pending), and discovery GET /discover (+ /discover/search) backed by App\Queries\
TalentSearch (spatie/laravel-query-builder) with ADR-6 search indexes (migration add_discovery_search_
indexes).
Discovery is skills-first (2026-07-11): the primary filter is Skills (talent_types) grouped by scope
(model/crew/creative); an "Advanced filters" modal (with active-filter count) holds the rest, revealed
by the selected skills' category — Location always; crew→Equipment, creative→Software, model→Looks;
free-text q demoted to a secondary control. TalentSearch whitelists type/category/city/country/equipment/
software/looks/q (availability gone, ADR-L). The Looks filter matches look_types.name (translatable JSON)
on the English path, backed by a functional index look_types_name_en_index (migration 2026_07_11_000100,
MySQL-only). Eager-loaded + paginated (no N+1); verified dark/light/RTL.
Discovery UX pass (2026-07-12): the Skills filter is now THE primary control — a sticky bar (top-16) with a
Skills heading, selected-count, an "All" reset chip sitting BESIDE the scope groups (not above), and multi-select
chips grouped by scope (the Modeling/Crew/Creative groups sit SIDE BY SIDE as divider-separated columns on ONE
line — nowrap + overflow-x-auto; the single-chip Modeling group keeps an invisible label placeholder so chips
align) showing talent_types.icon with real states (hover/focus-visible/filled-accent+check selected; aria-pressed
toggles in a labelled role="group"). "All" is a NEUTRAL RESET, not a default selection — it never shows a filled
state and is disabled while nothing is chosen (so an unfiltered view highlights no chip). The chips live in a
shared partial (public/partials/skill-filter-chips) used by both the bar ($nowrap) and the modal ($staged). Below the chips: an active-filter summary row of removable chips + "Clear all", and a
live result count ("N talents"). Filters sync to the URL (shareable + back/forward via popstate; discrete
changes pushState, q typing replaceState, pagination holds filters); Ajax with skeleton loaders + an empty
state (Clear filters); secondary q search stays de-emphasised. filter[type] is multi-select (comma-separated
slugs). The Advanced-filters modal is TELEPORTED to <body> (x-teleport) so no transformed/overflow ancestor
traps it — always opens centred in the viewport when scrolled, over a token --scrim; body scroll-locked
(position restored), closes on ×/backdrop/ESC, focus-trapped (returns focus to trigger), role=dialog+aria-modal,
bottom sheet with internal scroll on mobile; it is a LARGE dialog (sm:max-w-2xl, rounded-2xl) with a title +
subtitle, a Skills section, a divider, then Location + a "Skill-specific" section whose scoped selects sit in a
2-col grid. IMPORTANT: do NOT use Alpine x-transition on an x-teleport'd node —
its leave transition never completes and leaves a click-trapping overlay; drive enter/leave via :class + CSS
transitions with a mount→$nextTick(activate)→(leave)→unmount cycle and pointer-events-none while inactive
(dashboard.js talentSearch). The modal is WIDE (sm:max-w-3xl, title + subtitle) and holds a Skills section, a
Location section, and a Skill-specific section (scoped selects in a 2-col grid) shown BY SCOPE
(Equipment·Crew / Software·Creative / Looks·Modeling) driven by the DRAFT skills: a scoped filter appears ONLY
once its related skill is selected — with NO draft skill the section shows a hint ("Select a skill to reveal its
filters."), and picking a skill reveals its filter (showEquipment/Software/Looks = draftScopes.has(...), NOT the
old "|| draft.type.length===0"; hasScopedFilters gates the grid-vs-hint). It is a STAGING area: it edits a `draft` snapshot
(draft.type/city/country/equipment/software/looks) and NOTHING applies to the results until "Apply filters" —
applyFilters() commits draft→filters then searches; ×/backdrop/ESC discards; "Clear filters" resets the draft in
place (no search). The main-bar chips apply live (toggleType); the modal chips stage (toggleDraftType).
pruneScopedFilters (live) is self-contained on selectedScopes. Verified dark/light/RTL in-browser.
Public profile header is Instagram-style (2026-07-10, ADR-O): no cover/hero image (the `hero` media
collection + `hero_image_url` accessor + the editor hero uploader are removed; the `avatar` collection
stays; the `hero` block type is untouched, just skipped). Header = circular avatar + display_name +
@username + primary-skill/headline line + stats row (Projects=projects count · Views=view_count ·
Rating=avg approved reviews, hidden when none) + bio + optional external link + Pricing-rate chip +
Message/Leave-a-review CTAs. Token-only, verified dark+light+RTL; stats from eager-loaded relations (no N+1).
Public profile primary CTA is Message (2026-07-11, ADR-P, supersedes Contact): points at the reserved
public route brand.talents.message (App\Http\Controllers\Brand\TalentMessageController). Interim only —
guest/non-brand → brand login (?role=brand) with the profile stored as url.intended; authed brand →
"Messaging coming soon" flash back on the profile + a TODO(brand-messaging) hook where the real
brand↔talent chat / deal initiation attaches. No new tables; talent.enquire is untouched.
Deal engine complete (Phase 1E, shared infra — app/Deals, app/Actions/Deals, app/Services/DealService,
app/States/Deal|DealStep|DealMessage): deal_flows/deal_flow_steps/deals/deal_steps/deal_messages/
deal_enquiries + a MINIMAL brands stub table (Phase 1B extends). StepHandler Strategy+Factory (8 types;
PaymentStepHandler manual-vs-auto per ADR-B, default manual); Actions Snapshot/Initiate/Advance/
RejectStep(loop-back)/ConvertEnquiry sharing DealProgression; state machines; DealService in
transactions (deals channel). Booking CTA GET|POST /{slug}/enquire → deal_enquiries. Talent deal room +
inbox (routes/talent.php, resources/js/deals.js).
Deal-room layout is timeline-first (2026-07-11, layout-only): the message timeline is the central/wide
column with the composer; the narrower side panel holds the current-step action panel (top) then the
phases stepper (below); header on top (reference/title/counterparty/status/amount + "← All deals").
Blade-only change (resources/views/talent/deals/show.blade.php) — the dealRoom Alpine component,
endpoints, and DealService are unchanged; messaging + step actions still Ajax with no reload; dark/light
+ RTL verified. The brand deal room (Phase 2C) should reuse this layout.

TALENT PHASE COMPLETE (production-grade). Full Pest suite green; preventLazyLoading +
preventSilentlyDiscardingAttributes on (no N+1); every list paginated + eager-loaded; every multi-write
op wrapped in runInTransaction with fail-logging to the right channel (deals / media / app); all
colours/fonts are CSS tokens (cloud/graphite/teal + Bricolage/Sora), dark+light+RTL verified on every
talent page; every dashboard interaction is Ajax (no reload). Demo data: multi-type demo talent with
full blocks/content/images + three deals at different steps (awaiting_talent / awaiting_brand /
completed), plus 10 showcase talents across all six skills. Manual QA checklist in
docs/conventions.md ("QA checklist — talent slice").
Three talent features removed entirely (2026-07-10, ADR-K/L/M): the rate card / **services** (+ the
deal-engine `service_id` ripple; deal amount now comes from the flow's form/quote step, and the single
Pricing rate replaces the rate card), **availability & travel** (enquiries are no longer availability-
gated; `rate_tier` superseded by the Pricing rate), and **affiliations & press**. Schema removed via
append-only drop migrations (2026_07_10_0001–0004); their models, factories, state machines, policies,
routes/controllers/requests/resources, blocks, seeds, and tests are gone.
Profile consolidation + Skills rename + Pricing rate (2026-07-10, ADR-N): "Professions" renamed
**Skills** across UI/routes/symbols (route `professions`→`skills`; ProfessionsService→SkillsService,
ProfessionController→SkillController, StoreProfessionRequest→StoreSkillRequest) — the `talent_types`
table stays as the Skills catalog. The standalone Professions + Account tabs folded into the Profile
editor (Skills, Username=`slug`, Publish sections). New **Pricing rate** columns on `talents`
(rate_unit/rate_amount/rate_currency, migration 2026_07_10_000500; all-or-nothing, not translatable)
replace the removed rate card. Admin relabel + ProfessionCatalogService→SkillCatalogService apply when
the admin side is built (not present yet). Suite 160 green.
Next: Phase 2A — brand core & satellites (extend the brands stub: industry/stage/location/reach +
brand_aesthetics/images/creative_needs/credibility/reviews/social_handles/signals), then the brand
deal room (Phase 2C) reusing the shared deal engine; Admin authoring/intervention is Phase 3.
deals.campaign_id (ADR-F) lands with campaigns.
