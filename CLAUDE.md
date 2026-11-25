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
| Lifecycles & state transitions | State machine — spatie/laravel-model-states | contract, contract_step, contract_message, campaign, brand, talent profile, review, media |
| Configurable contract steps | Strategy + Factory — a StepHandler per step_type | the contract-flow engine: form / approval / upload / payment / contract / message / schedule / info |
| Discrete operations | Action classes (single-purpose, invokable) | seed/merge profile blocks, snapshot flow steps, initiate/advance contract, convert enquiry→contract, recalc credibility |
| Orchestration, transactions, logging | Service layer | every domain area; web + API controllers both call the same services |
| Decoupled side effects | Events / Listeners / Observers | contract completed → open review window + bump credibility + advance campaign; media uploaded → conversions; profile view → brand_signals |
| Authorization | Policies | own-resource edits; admin intervention/override |
| Typed input/output | DTOs — spatie/laravel-data | Form Request → Service → Resource, shared between web and API |
| Complex reads | Query objects / scopes + spatie/laravel-query-builder | discovery feed, contracts inbox filters, talent search |
| Files | spatie/laravel-medialibrary | all uploads (collections + conversions) |
| i18n | mcamara/laravel-localization + spatie/laravel-translatable | locale routing + translatable attributes |
| Audit | spatie/laravel-activitylog | admin edits, moderation, contract-step overrides |
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
Home · Profile · Content · Reviews · Contracts. The **Profile editor** is the single profile surface —
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
brand↔talent chat / contract initiation attaches. No new tables; talent.enquire is untouched.
Contract engine complete (Phase 1E, shared infra — app/Contracting, app/Actions/Contracting, app/Services/ContractService,
app/States/Contract|ContractStep|ContractMessage): contract_flows/contract_flow_steps/contracts/contract_steps/contract_messages/
contract_enquiries + a MINIMAL brands stub table (Phase 1B extends). StepHandler Strategy+Factory (8 types;
PaymentStepHandler manual-vs-auto per ADR-B, default manual); Actions Snapshot/Initiate/Advance/
RejectStep(loop-back)/ConvertEnquiry sharing ContractProgression; state machines; ContractService in
transactions (contracts channel). Booking CTA GET|POST /{slug}/enquire → contract_enquiries. Talent contract room +
inbox (routes/talent.php, resources/js/contracts.js).
Contract-room layout is timeline-first (2026-07-11, layout-only): the message timeline is the central/wide
column with the composer; the narrower side panel holds the current-step action panel (top) then the
phases stepper (below); header on top (reference/title/counterparty/status/amount + "← All contracts").
Blade-only change (resources/views/talent/contracts/show.blade.php) — the contractRoom Alpine component,
endpoints, and ContractService are unchanged; messaging + step actions still Ajax with no reload; dark/light
+ RTL verified. The brand contract room (Phase 2C) should reuse this layout.

TALENT PHASE COMPLETE (production-grade). Full Pest suite green; preventLazyLoading +
preventSilentlyDiscardingAttributes on (no N+1); every list paginated + eager-loaded; every multi-write
op wrapped in runInTransaction with fail-logging to the right channel (contracts / media / app); all
colours/fonts are CSS tokens (cloud/graphite/teal + Bricolage/Sora), dark+light+RTL verified on every
talent page; every dashboard interaction is Ajax (no reload). Demo data: multi-type demo talent with
full blocks/content/images + three contracts at different steps (awaiting_talent / awaiting_brand /
completed), plus 10 showcase talents across all six skills. Manual QA checklist in
docs/conventions.md ("QA checklist — talent slice").
Three talent features removed entirely (2026-07-10, ADR-K/L/M): the rate card / **services** (+ the
contract-engine `service_id` ripple; contract amount now comes from the flow's form/quote step, and the single
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
replace the removed rate card. The admin relabel landed with the admin phase
(ProfessionCatalogService→SkillCatalogService, ProfessionController→Admin\SkillController, routes
/admin/skills).

BRAND PHASE COMPLETE (Phases 2A–2C) — brand core + satellites, projects, brand contract room, brand
discovery, and the talent-facing brand/opportunity pages. ADR-F has **landed**:
`contracts.brand_project_id` (nullable, nullOnDelete) links a contract to its project.
**One project = one role = one position** (`brand_projects.talent_type_id`) — the
`brand_project_talent_types` pivot (many roles × `quantity`) and `positions_count` are gone.
**`budget_is_public` defaults to false**: a private budget is withheld from every non-owning viewer in
the payload (public profile, project detail, talent opportunity feed) — not merely hidden in the view.
Talents apply to a project via a rich-text modal (@-mention their own projects + attachments); brief HTML
is sanitized server-side by `App\Support\Html\BriefSanitizer` (DOMDocument allowlist).

Two repo-wide renames are DONE and are the current vocabulary — use them: **Campaign → Project**
(`brand_projects`, `BrandProject`, /brand/projects, /brands/{slug}/projects/{slug}) and **Deal →
Contract** (`contracts`/`contract_flows`/`contract_flow_steps`/`contract_steps`/`contract_messages`/
`contract_enquiries`, `ContractService`, permission `intervene-contracts`, log names `contract_flow` /
`contract_intervention`). Two things deliberately KEEP the old word: the `talent_types.category` enum is
still `model|crew|creative` (ADR-S renamed the **slugs** to disciplines — `modeling`, `photography`, … —
not the category enum; `applies_to` on a flow uses the category), and the `project_types` enum value
`campaign_video`. `docs/changelog.md` is dated history and intentionally still says "deal" below
2026-07-15.

ADMIN PHASE COMPLETE (Phase 3, reconciled 2026-07-16). `php artisan migrate:fresh --seed` exits 0 and the
full Pest suite is **345/345 green**. Admin = `admin` guard + spatie/laravel-permission RBAC (roles
super-admin/moderator/support; permissions manage-flows, moderate-content, intervene-contracts,
manage-settings, manage-users; `User` has `$guard_name='admin'`). Surfaces: dashboard, **flow builder**
(/admin/flows — draft→active→archived, one default per `applies_to`), **skills catalog**
(/admin/skills), **moderation queues** (/admin/moderation/{talents,reviews,brands,brand-reviews,
projects}), **contract intervention console** (/admin/contracts — override/advance-as-admin/nudge/
reassign/cancel via ContractInterventionService, reusing the 1E engine at **`App\Contracting\`** — NOT
`App\Contracts\`, which is Laravel's interface namespace), activity log, settings, admin users.
Editing a flow affects **future contracts only** (contracts snapshot their steps at creation).
The **Deposit step is mandatory** (`is_skippable => false`) — it locks the booking.
Audit: `ContractFlow`/`ContractFlowStep` apply `LogsActivity` + `getActivitylogOptions()` (log
`contract_flow`, `logOnlyDirty()`, `dontLogEmptyChanges()` — activitylog 5.0 has **no**
`dontSubmitEmptyLogs()`); causer resolves from the `admin` guard because
`activitylog.default_auth_driver` is null → default guard → `admin`. Model old/new live under
**`attribute_changes`**, not `properties`.
`DatabaseSeeder` order matters: RolesAndPermissionsSeeder → catalogs → ContractFlowSeeder →
SettingsSeeder → demo data (AdminDemoSeeder authorizes against the RBAC; settings need the default flow).
Demo logins: admin `admin-demo@fama.test`, talent `talent-demo@fama.test`, brand `brand-demo@fama.test` —
all password `password`. (RolesAndPermissionsSeeder grants super-admin to `admin-demo@fama.test`.)

Admin reachability + block governance split (2026-07-16, ADR-T; suite 345 green). `resources/js/admin.js`
is now imported by `app.js` — it never was, so every admin page had shipped as a dead Alpine shell in the
browser (HTTP tests can't see this; check the built bundle). Grouped, permission-gated sidebar
(Moderation queues deep-link tabs via server-validated `?queue=`, URL synced with replaceState;
`aria-current` marks the active item incl. the matching queue) + a dashboard home where EVERY page has a
gated card/quick link: moderation counts (pending talents = created/draft, pending reviews ×2, unverified
brands) with "All clear." empty states, contracts whose-turn card, projects-by-status card, governance
quick links, recent activity (causer eager-loaded) — all single aggregate queries computed only when the
admin holds the permission. **Block governance is split (ADR-T):** `/admin/blocks` (NEW permission
`manage-blocks`, super-admin only) owns block-type existence + eligibility (availability gates synced to
mode, `is_active` grandfathering — the talent picker filters, existing placements keep rendering;
`key`/`content_source` locked once in use → 422; `settings_schema` must be valid JSON) via
BlockCatalogService; `/admin/skills` now edits ONLY ordered preselection among catalog-eligible blocks
(`ProfileBlockService::isEligibleForScope` is THE eligibility predicate — reuse it, never reimplement),
flags stale keys "no longer eligible", and `updateDefaultBlocks` rejects ADDING an ineligible key while
allowing reorder/removal around a stale one. Also: global review queue (`/admin/moderation/all-reviews`,
UNION + per-kind hydration, kind routes actions), projects oversight status filter + budget always
visible to admins (tagged private), contract console current-step filter, brand Unpublish surfaced.
Envelope meta nests pagination under `meta.pagination.*` (http.js passes it through as-is).
Moderation detail drawers + admin polish (2026-07-16, suite 350 green): every moderation queue row opens
an end-side detail drawer (5 `show*` endpoints, `GET /admin/moderation/{queue}/{id}`, kind-aware actions
inside; the global queue resolves per row). Shared admin UI kit: `<x-admin.skeleton>` loaders,
`<x-admin.pagination>` (meta.pagination-driven), and the Alpine magic `$pill(status)` in admin.js — ONE
semantic status→tone mapping for all admin lists. WATCH OUT: `text-ok`/`bg-ok` are NOT design tokens
(they render as nothing) — the real tokens are `success`/`success-weak` (+ warn/danger variants).
N+1 watch-out: medialibrary `*_url` accessors (e.g. `talent.avatar_url`) call `getFirstMediaUrl`, which
uses `loadMissing` internally — so `preventLazyLoading` will NOT catch them. Any list whose Resource
touches an avatar must eager-load `talent.media`.

Auth + error pages are on the Fama design system (2026-07-16, presentation-only; suite 356 green).
Breeze's low-level partials (input-label/text-input/input-error/auth-session-status/primary-button) are
token-bound — restyle THOSE, not per-form markup; `x-text-input` takes an `error` prop (aria-invalid +
danger border). The guest layout is the public-layout treatment (wordmark header + theme/locale toggles +
centered surface card + `animate-fade-in-up`). Ajax is locale-aware: `http.js` prefixes same-origin requests with the active locale (from
`<meta name="locale-prefix">` in design-head); nav links built in JS must localize via
`window.fama.localizeUrl(...)` or they drop to the default locale. JS pills localize status via the
`$statusLabel()` magic (label map from design-head) — never render a raw status string; likewise
`$actorLabel`/`$stepLabel`/`$categoryLabel`/`$flowLabel`. Contract-timeline system events store a
structured `meta` ({key,params}) on `contract_messages` and are localized at render by
`ContractMessageResource` in the VIEWER's locale (English `body` is the fallback); actor/step display
labels live in `App\Support\ContractLabels` (shared by the resource + design-head). When adding a new
system-event verb, extend `ContractProgression::stepEventMeta()` + the resource's match + the AR lang
templates. Moderation
Suspend/Unpublish are STATE-AWARE toggles (Suspend⇄Reinstate, Publish⇄Unpublish) via
publish()/unsuspend() on the moderation services; the shared `admin/moderation/partials/account-actions`
partial drives both rows and the drawer.
Destructive actions (delete/remove/cancel) MUST go through the global confirm modal: wire the button as
`@click="$confirm({ title, message, confirmLabel, tone }).then(ok => ok && action())"` (promise-based
Alpine store in resources/js/confirm.js; dialog partial `<x-confirm-dialog>` is already in the three
dashboard layouts). Keep the action method unchanged; copy stays in Blade (`__()`). Don't add bare
delete handlers or new ad-hoc inline confirms.
Account creation goes through ONE service — `AccountCreationService` (createTalent/createBrand/
createAdmin) — used by BOTH public `/register` (talent|brand only, ADR-I) and admin `/admin/users`
(all three; brand/talent surface in moderation, not the admins list). New self-serve talent = draft,
brand = registered, both unpublished. `Brand` now has a guarded auto-slug `booted()` hook like `Talent`.
Login + register are premium standalone SPLIT-SCREEN pages (their own `<html>` layouts, graphite
showcase panel + form — NOT the guest card; forgot/reset/confirm/verify still use `<x-guest-layout>`).
The PUBLIC login is role-aware for Talent | Brand ONLY
(2-segment control of native radios, name=`role`, `@checked`; absent role defaults to TALENT;
`?role=brand` per ADR-P; absent role → TALENT; `LoginRequest` REJECTS role=admin). Register's
`account_type` is talent|brand (rich cards); admin `/admin/users` create form adds an account-type
control (admin shows roles; brand/talent don't). STAFF sign in at **/admin/login**
(`admin.login[.store]`, `guest:admin`, `Admin\Auth\AdminLoginController` + `AdminLoginRequest` —
subclasses LoginRequest, guard pinned to admin, no role field, lands on admin.dashboard) with its own
enterprise split-screen view `admin/auth/login` (noindex). `redirectGuestsTo` routes admin-area guests
(first path segment, or second after a 2-letter locale) to the staff login. Password fields use
`<x-password-input>` (show/hide toggle, aria-pressed, no-JS-safe static type). /ar URLs are NOT
testable in-process (mcamara registers the locale prefix per request) — verify RTL live. The app owns `errors/{403,404,419,429,500,503}` on
`errors/layout` — dependency-light (no Alpine/data; `rescue()` for the dashboard link); keep them
rendering when degraded.

PHASE 5 (public Feed) IN PROGRESS — playbook at `fama-feed-playbook.md` (prompts 5A–5R, run in order).
**5A done (2026-07-22, docs only):** canonical `docs/specs/feed-spec.md` (entities, permission matrix,
two-tier seeded ranking + cached candidate-ID pagination, mentions as ID+offset, zero-network link
chips + async enrichment, notification matrix, moderation, guest intent replay, open questions Q1–Q7);
schema-master §7 adds the ten feed tables (35 → 45: posts, post_skill, comments, likes, follows,
mentions, link_previews, linked_urls, post_reports, feed_seen + counter columns on talents/brands) and
§5 was reconciled with implemented ADR-F reality; ADR-F1…F8 appended to docs/decisions.md (F2 carries a
stakeholder flag — project shares live-reference + snapshot fallback, deviating from the contract-step
snapshot rule). Feed vocabulary: the shared thing is the talent-owned **Project** (`projects`), authors
are polymorphic (aliased morph map talent/brand/user/post/comment/project — MUST fold in the existing
contract-engine morph keys before enforcing), posting is talent-only via PostPolicy (ADR-F1), the
Daylight design system (playbook §3) is FEED-SCOPED — a second token set, not a restyle of cloud/
graphite/teal.
**5B done (2026-07-22, suite 396 green):** the ten feed tables migrated (2026_07_22_000100, all
composite indexes) + counter columns; models Post (HasMedia post_media, queued WebP thumb/feed/full,
scopePublished, isEditable), Comment, Like, Follow, Mention, LinkPreview, LinkedUrl, PostReport,
FeedSeen; PostState (draft→published⇄hidden→removed) + CommentState; HasFeedIdentity concern on
Talent+Brand (posts/followers/following/likesGiven + feedUsername/feedDisplayName/feedAvatarUrl);
CounterService (sole counter mutator, zero-clamped) + #[ObservedBy] observers so counters move
in-transaction; feed:reconcile-counters (daily 03:10, derived-table join for replies_count — MySQL
1093) with corruption-repair test; NEW `feed` log channel; config/feed.php holds every tunable.
**WATCH OUT — enforced aliased morph map (App\Support\MorphMap, ADR-F1):** every model now morphs as
a snake_case alias ('talent','post',…); adding ANY new model that can appear in a morph column
requires adding it to MorphMap or writes throw ClassMorphViolationException; aliases are FROZEN
(changing one = data migration); migration 2026_07_22_000200 converted legacy FQCN rows (media,
activity_log, permission pivots, sanctum tokens, contract morphs) — flush the spatie permission cache
after deploying it.
**5C done (2026-07-22, suite 410 green):** App\Feed\{Actor, CurrentActor, PendingIntent} — Actor
implemented by Talent+Brand; CurrentActor resolves talent→brand (STATELESS on purpose — memoization
leaks across requests in tests/Octane), admins are never actors; middleware resolve.actor (never
rejects, shares $currentActor) + require.actor:<action> (guest → 401 envelope with data.intent
{action,target_type,target_id,payload}, session-captured; admin → 403); ReplayPendingIntent listener
replays like/follow/comment EXACTLY ONCE on Login (clears before executing, failures → feed log).
Policies auto-discovered: PostPolicy (create=Talent only — the ADR-F1 flip point; update windowed
15min; delete=author ANYTIME — deliberate deviation from 5C's windowed-delete wording, per D12/5F),
CommentPolicy, LikePolicy, FollowPolicy (self-follow deny), PostReportPolicy (once per target).
routes/feed.php (inside locale group, BEFORE the /{slug} catch-all): /feed + /posts/{post} interim
shells (unpublished → 404 not 403), comments GET/POST, like POST/DELETE, follow POST/DELETE
/follow/{type}/{id}. app/Actions/Feed/{ToggleLike,ToggleFollow,CreateComment} are minimal-but-final
(race-safe unique-index idempotency, edge rules, depth-two re-attach + auto-@mention) — 5D/5G/5H
ENRICH these in place, don't recreate. Feed controllers extend FeedBaseController (gate() bound to
actor/admin/guest) and NEVER call Auth::guard().
**5D done (2026-07-22, suite 419 green):** app/Data/Feed DTOs (ParsedBodyData/MentionData/UrlData);
RichTextParser (char offsets via byte→mb conversion — Arabic-proven; e-mail lookbehind guard;
talents-then-brands slug resolution in 2 whereIns; never throws); SyncMentions/SyncLinks (idempotent
delete-and-reinsert on create AND edit, limits → ValidationException naming the limit) wired into
CreateComment's transaction; ResolveLinkPreview (normalize → sha256 url_hash → firstOrCreate pending
— 5E swaps the parse for the driver registry BEHIND THE SAME SIGNATURE); BodyRenderer (escape-first
+ inject, current-Username at render, <bdi> + LRI/PDI isolates on every token — callers MUST
eager-load mentions.mentioned + linkedUrls.preview); GET /mentions/search (throttle 30/min,
published-only, 8 grouped talents-first).
**5E done (2026-07-22, suite 466 green):** App\Feed\Links — LinkProvider contract + AbstractProvider
+ 14 drivers (instagram/tiktok/youtube/behance/vimeo/x/linkedin/pinterest/dribbble/spotify/
soundcloud/facebook/snapchat/generic) resolved by LinkProviderRegistry from config/feed.php
'providers' (add a provider = class + config line; generic auto-appended last);
ResolveLinkPreview now registry-parses + dispatches FetchLinkPreviewJob afterCommit on FIRST sight
only; the job does oEmbed (config enrichment.oembed) else OG, 3 tries backoff, terminal failure →
status failed (chip stays); UrlGuard = the SSRF wall (scheme allowlist, DNS private/reserved
rejection per hop, manual redirects ≤3, 5s, 512KB, embed_html only youtube/vimeo reduced to a
vetted iframe). POST /links/preview (require.actor:link + throttle:20,1).
**WATCH OUT: phpunit QUEUE_CONNECTION is now `database`** (NOT sync — a sync queue ran enrichment
inline in tests and made real network calls); assert dispatches with Bus::fake or rows in `jobs`,
and unit-test job logic by calling handle() directly.
**5F done (2026-07-22, suite 477 green):** app/Actions/Feed/{CreatePost,ShareProjectAsPost,
UpdatePost,DeletePost,TogglePinPost} — all transactional; skills own-only (validated IN the actions);
UpdatePost takes NO files (media immutable after publish); TogglePinPost unpins the previous (max 1);
ShareProjectAsPost gates ownership + is_published profile and freezes shared_snapshot {title
translations, cover_media_path, shared_at}. StorePostRequest (substance rule + PostMediaFile —
ffprobe duration check ONLY when binary present, size caps always), UpdatePostRequest,
ShareProjectRequest. Routes: POST /posts (throttle:10,60), PATCH/DELETE /posts/{post},
POST /posts/{post}/pin, POST /projects/{project}/share. Post has a queued `poster` video conversion
(needs ffmpeg on worker). FeedSeeder (config feed.seeder sizes; tests shrink) seeds the UI dataset —
AR/EN bodies via the REAL parser pipeline, GD imagery, follow graph, likes, threaded comments —
and MUST end with CounterService::reconcile() because DatabaseSeeder's WithoutModelEvents mutes the
counter observers. Dev DB seeded (300 posts).
**5G+5H done (2026-07-22, suite 489 green):** ReplyToComment (thin — CreateComment is THE single
comment write path), UpdateComment/DeleteComment (window; tombstone = trashed parent with surviving
replies stays in the listing); thread listing paginated 10 newest-first with 3 preview replies +
remaining_replies + liked_by_viewer via constrained `viewerLike` eager-load (guest constraint 1=0;
Post and Comment both have the relation) — fixed-query-count proven; replies paginate separately
oldest-first; comment like endpoints; Resources app/Http/Resources/Feed/{ActorResource,
CommentResource} (body_html via BodyRenderer — callers eager-load mentions.mentioned +
linkedUrls.preview). NAMED per-actor rate limiters in AppServiceProvider (feed-posts 10/h,
feed-comments 10/min, feed-likes + feed-follows 60/min, feed-links 20/min, feed-mentions 30/min;
key = actor morph:id else ip) + friendly 429 envelope renderer in bootstrap/app.php.
FollowService::isFollowing = THE bulk "type:id"=>bool map (one query, guests all-false);
GET /talents/{username}/followers|/following paginated 24 with followed_by_viewer.
**5I done (2026-07-22, suite 496 green):** FeedService — candidate IDs frozen per viewer+seed
(cache 10min, key feed:candidates:{morph-id|guest}:{seed}); tier1 seeded shuffle md5(id.seed);
tier2 scored (affinity: talent=own skills, brand=followed talents' skills via one join, guest=none;
velocity log-damped; freshness; recency half-life) + seed jitter; interleave (inject 1 tier2 per 4
tier1) THEN diversity cap ≤2 consecutive (deferred-queue algorithm — cap may move the injection
earlier than position 4, tests must assert invariants not positions); casting slot = every 6th
GLOBAL position (slice-relative indexes in the response); hydration one whereIn+FIELD with
constrained viewerLike; cursor = base64 {s,o}. DiscoverTalentsService: strip of 12 (pool cache 5min,
shown-set per viewer+seed → no repeats in-session). GET /feed/items + /feed/casting; guest seed =
sha1(session id) (D14). PostResource: media derivative urls fall back to original until queued
conversions run; project payload = live-or-snapshot (ADR-F2). All ranking knobs in config/feed.php
'ranking'.
**5J done (2026-07-22, suite 503 green):** App\Feed\FeedNotifier = the ONLY dispatch point (self-
guard, 60-min like aggregation updated IN PLACE via data->target "type:id" string key, mention
suppresses comment/reply for the same event, refollow-silent, deep links, guard() logs failures to
feed channel — a silent notifier bug shows up THERE, not in the response). 8 thin queued classes
under app/Notifications/Feed extending FeedNotification (afterCommit() in constructor — NEVER
redeclare $afterCommit, Queueable owns it; that fatal is invisible under the pao output wrapper —
debug silent test runs with `PAO_DISABLE=1 ./vendor/bin/pest`). Payloads store message KEY + params,
localized at read (lang/ar.json has the feed strings). Endpoints: GET /notifications,
POST /notifications/read, GET /notifications/unread-count (require.actor). notifications table
migrated (uuid + morph indexes). Actions call the notifier POST-COMMIT.
**5K done (2026-07-22, suite 510 green):** fragment endpoint (envelope {html} via
feed/partials/post — 5M swaps THIS), /talents/{username}/posts (pinned first), report endpoints
(once-per-target), LinkChipResource + NotificationResource. routes/api.php = /api/v1 mirror through
the SAME controllers (registered via bootstrap withRouting api:); CurrentActor guards now
['talent','brand','sanctum']; RequireActor captures intent only when $request->hasSession();
FeedController::sessionSeed falls back to actor/fingerprint+hour when stateless. Scribe live:
`php artisan scribe:generate` → /docs + storage/app/private/scribe/{openapi.yaml,collection.json}.
docs/api.md: 190 endpoints (130 + 31 feed web + 29 api).
**5L done (2026-07-22, suite 510, vite build green):** resources/css/daylight.css — Daylight is
SCOPED under [data-surface='daylight'] and RE-READS the core tokens (--bg/--surface/--ink/--accent/
fonts) from the Daylight palette, so existing utilities/components just work inside the feed;
--dl-* holds Daylight-only values; new Tailwind names bg-sand/sand-deep/chalk/veil, text-daylight,
border-seam. WATCH OUT: clip-path clips box-shadow — the notch lives on .frame, the BLUE shadow on
the .frame-shadow wrapper via filter:drop-shadow (follows the notched silhouette); compose
<div class="frame-shadow"><div class="frame">. Glint = .glint + .is-lit + .frame-shadow.is-sweeping.
Fonts via feed/partials/daylight-head.blade.php (feed pages only; self-hosting = deploy-time swap,
docs/design-system.md). Preview: GET /design/feed-tokens (both themes × both dirs).
docs/design-system.md is now part of the fixed docs set.
**5M done (2026-07-22, suite 510 + build green, verified live):** components/feed/{frame,media,
spread,credits,actions,casting,skeleton}; feed/partials/post.blade.php = THE canonical frame
(server page 1 + /feed/items html[] + fragment endpoint all render it); FeedController::index
serves page 1 of the RANKED stream (same frozen list as the cursor); /feed/items adds html[] +
casting_html for web only (API stays JSON). resources/js/feed.js: feedStream (sentinel observer,
double-fetch guard, casting injection, light-bar), postFrame (optimistic glint + is-sweeping
restart via offsetWidth reflow, 401 → inline gate with talent/brand chips), castingCard. Comments
mount is a placeholder — 5O fills it.
**5N done (2026-07-22, suite 510 + build green, live 200):** feed/partials/composer.blade.php
(talent-only; FeedController::index passes $composer with own skills+projects) + feedComposer in
feed.js. Body is a TEXTAREA (deliberate deviation from contenteditable — native caret/RTL wins;
documented); @caret-token → debounced /mentions/search (arrows/Enter); URLs → /links/preview instant
chips; media DnD/reorder/client caps; share-a-Project mode; LIVE credits preview; submit → fragment
prepend, draft persists in memory. NO @alpinejs/focus in this repo — never use x-trap; manual focus
+ body overflow lock. WATCH OUT: never Cache::remember Eloquent MODELS (unserializes as
__PHP_Incomplete_Class live) — DiscoverTalentsService now caches ranked IDs + rehydrates via
FIELD().
**5O done (2026-07-22, suite 512 green):** feed/partials/comments.blade.php + commentThread
(NESTED in postFrame — reads commentsOpen/commentsCount from parent scope; lazy loadOnce via
x-effect); tombstones, preview replies + separate reply pagination, optimistic comment likes,
reply pre-fill @username, inline edit with mm:ss countdown (CommentResource has is_mine +
editable_until), delete via $confirm. Double-tap like on media; report = reason-sheet radio chips +
note + toast. Guest loop CLOSED: RequireActor stores session 'feed.return_to' (post/comment anchor)
next to the intent; AuthenticatedSessionController honours feed.return_to (a DELIBERATELY separate
slot from url.intended, which multi-guard login drops); e2e test proves gate → login → replay →
anchor.
**5P done (2026-07-22, suite 515 green):** /feed?skill={slug} + /feed?following=1 — filters live
INSIDE the cursor ({s,o,k,f}) AND the candidate cache key (frozen-list stability holds per filter);
first page reads query params, later pages read the cursor; header filter pills Ajax-swap the spine
(pushState). /discover results row links "See their work in the feed →" with the selected skill.
**WATCH OUT — feed FABs must be teleported (2026-07-23 fix):** the New-post + Back-to-top floating
buttons live inside `<main class="animate-fade-in-up">`, whose keyframes animate a `transform`. A
transformed ancestor is the containing block for `position:fixed`, so an in-place FAB stack pins to
the bottom of the full-height `<main>` (below the fold), not the viewport — the buttons vanish. They
are now `x-teleport`'d to `<body>` inside a `data-surface="daylight"` host (`.fab-host{display:contents}`
carries the token scope without painting a ground). Bottom `inset-inline-start` = left in EN, right in
AR. Anything fixed-positioned inside the daylight feed has this trap — teleport it.

**DASHBOARD FOLLOW MANAGEMENT COMPLETE (Prompt 5-follow, 2026-07-23): suite 587 green (2113
assertions), build green.** FOLLOWING + FOLLOWERS sub-tabs on the talent AND brand dashboards
(Talent|Brand\FollowsController, /{guard}/feed/follows[/data]); shared partial partials/feed-follows
rendered inside a data-surface="daylight" region so the RAIL card language applies (portrait talents,
square brands — SHAPE is the type signal, no colour; Fraunces display, mono handle, --ink-faint).
FollowManagementService pages the graph 24/page. WATCH OUTS: (1) the polymorphic other party MUST be
eager-loaded via MorphTo::morphWith (Talent=>[talentTypes,media], Brand=>[media]) — a plain
with('followable') N+1s across the mixed types; a fixed-query-count test (3 vs 15) guards it. (2)
Mutuality is ONE bulk query — FollowService::isFollowing (followers side) + the NEW followsBack
(following side, inverse map) — never per row. (3) Search + Skill filter + non-recency sorts run in
SQL (left-join talents+brands on the morph column, COALESCE; whereExists on the Skill pivot) — NOT an
in-memory filter of a fetched page, which breaks pagination. (4) talents.last_posted_at (migration
2026_07_22_000700, indexed) is the "quiet since >90d" signal — maintained by
CounterService::applyPostPublishDelta on the publish boundary (recompute-from-source on unpublish so
deleting the last post clears it) + reconciled nightly with a NULL-safe <=>. (5) The mutual badge +
quiet-since are dashboard-only — they must NOT leak into the public FollowListController payload
(asserted). (6) The dashboard uses design-head, NOT daylight-head, so window.famaLang is undefined
there — pass runtime strings (the undo label) from Blade into the Alpine component, don't read
famaLang. Optimistic unfollow = 280ms slide-out + 5s undo restoring the row IN PLACE; FOLLOWERS tab =
FOLLOW BACK where not mutual, no unfollow; both reuse /follow/{type}/{id}.

**FEED RAILS 5Z+5AA COMPLETE (2026-07-22): suite 560 green (2033 assertions), build green, live
/feed 200, 200 endpoints.** feed.allow_following_brands is now TRUE (brands.followers_count exists
— tests exercising the off-path must set the flag explicitly). SuggestionService = tiered ranking
(config feed.suggestions; tier bonuses dominate the signal base), truthful {key,params} reasons
translated at SERVE time, exclusions incl. the cache-tracked shown-unacted-7-day rest; cache = 5min
plain arrays per actor, forgotten on follow/dismiss/own-profile-update (Talent::updated +
Brand::updated hooks in AppServiceProvider); guest seed STORED in session ('feed.suggest_seed' —
session IDs regenerate, stored data survives). Endpoints /feed/suggestions/{talents,brands}
(?format=html returns the server-rendered suggestion-cards fragment for the rails) + dismiss
(60/min; client delays the POST 5s for undo — server dismissal is permanent). 5AA: per-post meta
rail DELETED (frame has no $rail slot anymore); .feed-grid three columns (talent rail ≥1400,
brand rail ≥1100, no-brand-rail rebalance for brand viewers); rails hydrate post-paint, empty →
collapse; inline strip halves to every-12 when a rail shows (feedStream.allowStripInjection).
Rail open questions for stakeholders: viewer's own anchor card, brand-to-brand suggestions,
260px vs 280–300px rail width (eyeball live).

**ROUND 3 (5AB–5AI) COMPLETE (2026-07-22): suite 574 green (2076 assertions), build green,
migrate:fresh --seed exits 0 (40 talent avatars + 15 brand logos seeded), live /feed 200 with a
CLEAN browser console (verified via Chrome, logged in as the demo talent).** Z-scale tokens in
app.css :root (--z-base 0 … --z-toast 500, --topbar-h) — NO raw z-indexes in the feed. Fixed
opaque public topbar (scroll hairline, <1100px hide/reveal, topbar.js), nav = Feed · Discover ·
Brands · Opportunities (Feed-first per playbook default; Discover-first = reorder $navItems in
public-layout), aria-current + gestural SVG underline. FAB stack (150vh IO marker), rebuilt action
bar (40px hits, share popover, SAVE + overflow menu, aria-labels name the author), generic
<x-feed.media-grid> + teleported lightbox, media_alt[] → medialibrary custom property 'alt'
(order-matched; StorePostRequest max:160). Report reasons are CONFIG (feed.report_reasons, 7 keys;
migration 2026_07_22_000600 enum→string + legacy mapping); 'other' requires note; shared report
dialog + $store.feedToasts rack. Comment thread: normal-case authors + mono handle/time +
comment-badge, collapsed→expand composer, optimistic pending rows with Retry. Composer modal:
portalled, pinned header/footer, PUBLISH hover-hint (publishHint names what's missing), dirty-close
$confirm, per-tile reorder/ALT/remove on the real .media-grid geometry.
ROUND-3 WATCH OUTS (each cost a live bug): (1) **@js() on a COMPONENT-TAG attribute ships
literally** — Blade compiles component-tag attributes as strings and only processes {{ }};
use {{ \Illuminate\Support\Js::from(...) }} there (RoundThreePolishTest guards the rendered feed
against literal directives). (2) **Never put a string :style binding on an element with x-show** —
each re-eval rewrites the style attribute and wipes x-show's display:none. (3) **$dispatch from
inside an x-teleport'd node bubbles from <body>** — dispatch from the component ROOT
($root.dispatchEvent) or the parent's listener never fires (mention-field pick). (4) **Bare
requestAnimationFrame never fires in hidden tabs** — rail hydration is rAF + 250ms setTimeout
fallback; don't gate data loads on rAF alone. (5) The **$confirm dialog markup** now also mounts
in public-layout (feed uses it) — dashboards were already covered. (6) **window.famaLang** (defined
in feed/partials/daylight-head) is THE bridge for every runtime JS string in feed.js — add the key
to BOTH the bridge and lang/ar.json when introducing one; it did not exist before round 3 and all
runtime strings were silently English. Rails: follower counts as words (N FOLLOWERS / NEW),
avatar-fallback tint from username hash, reason lines clamp to 2 lines (full @handle survives).

**REVISION BATCH 5S–5Y COMPLETE (2026-07-22): suite 551 green (1998 assertions), build green,
live /feed 200.** Key state: Daylight palette DERIVES from --dl-base = the topbar colour (core
--bg #EDEFF3/#0D0F13 — NOT the screenshot's #F4F2EE, which was the 85%-alpha bar over old sand);
sand/sand-deep/chalk RETIRED (ground/ground-sunk/surface/surface-sunk/seam/ink/ink-soft/ink-faint
via color-mix in oklch; ink-faint tuned to 56% both themes); `php artisan feed:check-contrast` is a
SUITE-ENFORCED WCAG gate — edit daylight.css and the command's formula table TOGETHER. Accent is
banned on buttons/chips/fills: .dl-btn (ghost→ink-fill), .dl-chip (active = ink), .well (soft halo
focus, padding-inline-end 48px, autogrow). ONE mention component: <x-feed.mention-field>
(x-modelable; submit rides 'submit-intent' event; /mentions/search excludes actor + ranks
followed>prefix>substring). Chips REPLACE raw URLs (BodyRenderer re-parses raw body for offsets —
never string-search the NORMALIZED stored url; glyph sprite feed/partials/glyphs; ChipLabel rules;
chips[] in API resources). Spread = strongest frame (widened shared_snapshot: title/cover/client/
year/media_count/skill_ids — ADR-F2 updated); credits = author ONCE with middot-joined skills +
WITH lines, handles NEVER uppercased (.credit-handle); rail = date+skills only. Bookmarks (5X):
polymorphic both sides, posts-only via ToggleBookmark type-gate, viewerBookmark eager uses
bookmarker_* columns (NOT the liker closure!), SAVED/LIKED dashboards private (ADR-F9), 'bookmark'
in PendingIntent. Motion (5Y): JS-applied entrances (transform/opacity, stagger cap 6, first-paint
exempt), light response = pre-rendered notched ::after shadow crossfade (never animate box-shadow),
content-visibility on frames, condensing sticky chrome; feed.js's motion layer IS the scroll-motion
layer (none pre-existed) — re-arm observeFrames() after ANY spine swap.

**PHASE 5 COMPLETE (5R closed 2026-07-22): suite 527 green (1901 assertions), build green,
migrate:fresh --seed exits 0, live /feed 200.** Final counts: 190 endpoints, 45 domain tables +
notifications. docs/performance.md is part of the fixed docs set (budgets are TEST-ENFORCED — keep
them green, update the doc when a budget changes). Open stakeholder items: ADR-F2 confirmation
(project-share live-ref+snapshot), video scope (Q3), AR copy pass on new feed Blade labels, live
RTL/dark browser verification. Extension points: brand posting = PostPolicy::create; trending =
FeedService tier-2; "Spark" label = lang swap; self-hosted fonts = daylight-head partial swap.

**5Q done (2026-07-22, suite 523 green):** FeedModerationService (state-machine actions in
transactions, activity log `feed_moderation` with causer+reason, removals notify the author via
ContentRemoved; registerReport = auto-hide tripwire at config feed.auto_hide_reports open reports,
wasAutoHidden() derives the queue flag from the activity log). Console at /admin/moderation/feed
(moderate-content; sidebar entry; reports/posts/comments queues via server-validated ?queue=;
talent activity timeline /admin/moderation/feed/talents/{talent}). feed:prune scheduled 03:40
(feed_seen >60d; failed+expired+UNREFERENCED link previews only). Admin actions PATCH
/admin/moderation/feed/{posts|comments}/{id}/{hide|remove|restore} + /reports/{id}/{actioned|
dismissed}. WATCH OUT in admin tests: a test session can hold SEVERAL guards at once — split
authorization checks into separate tests or the earlier admin login leaks through auth:admin.
Next: Prompt 5R — hardening close-out (query profiling + EXPLAIN → docs/performance.md, cache
inventory, final docs sweep, endpoint/table counts).

POST-PHASE MODERATION HARDENING (2026-07-31 → 2026-08-02, suite 604 green, build green,
migrate:fresh --seed exits 0):
- **Admin review deletion with an emailed notice (2026-07-31).** `reviews` soft-deletes now
  (`deleted_at`, migration `2026_07_31_000100`); `ReviewModerationService::deleteWithNotice` snapshots +
  soft-deletes + audits (`review.deleted`) then mails `App\Mail\ReviewRemovedNotice`
  (`emails/review-removed`). `POST /admin/moderation/reviews/{review}/delete` (moderate-content;
  email/subject/reason). The review drawer has a **Delete…** composer modal with a LIVE email preview.
  Comment authors already self-delete (CommentPolicy + feed `is_mine`); reviews are anonymous so admin
  removal is their only path. MAIL_MAILER=log in dev — the notice renders to the log.
- **Contract-template builder + live document preview (2026-08-03, ADR-V, suite 613 green).**
  `/admin/contract-templates` (gated `manage-flows`) authors a reusable contract "schema": standard-terms
  params (deposit %, holder, forfeit %, revision rounds) + a fully custom, reorderable `clauses` list with
  numeric tokens. Two-column builder — author left, WYSIWYG **live document preview** right (debounced
  `POST …/preview` → `{html}` renders the SAME `contracts/partials/document.blade.php` the contract room
  uses). Applied per flow via `contract_flows.contract_template_id`; at contract creation the terms FREEZE
  onto `contracts.terms_snapshot` (InitiateContract) so template edits never rewrite a live contract. ONE
  renderer: `App\Contracting\ContractDocument` (template→preview / contract→room). Talent+brand rooms show
  a collapsible **Contract** document. `ContractTemplate` model + `ContractTemplateService` (audited on the
  `contract_flow` log); morph alias `contract_template` (MUST stay in App\Support\MorphMap). Default "Fama
  Standard" template seeded + linked to Standard Booking. WATCH OUT: any new model that hits an activity/
  morph column needs a MorphMap alias or writes throw ClassMorphViolationException.
- **Mandatory admin verification before public (2026-08-02, ADR-U) — TALENT HALF SUPERSEDED 2026-11-04 by ADR-AC (verification is a non-gating badge; brands unchanged).** `is_verified` is the go-live gate
  for BOTH entities. Talents gained `is_verified` (migration `2026_08_02_000100`); `ToLive` now needs
  `display_name` AND `is_verified`; brands get a guarded `ToPublished` (needs `is_verified`). Self-serve
  publish (`TalentProfileService::publish`, brand `AccountController::publish`) pre-checks with a friendly
  422; the talent/brand editors show an *Awaiting verification* banner + disabled Publish. Admin: new
  `TalentModerationService::verify` + `verify` moderation action (audited `talent.verified`); the shared
  `account-actions` partial shows **Verify** for both kinds and hides **Publish** until verified. Verify
  is ONE-WAY (use Suspend/Unpublish to take down). Migration grandfathers live/published rows to verified;
  factory defaults for published/live states are `is_verified=true` (with `unverified()` states). Open
  owner decision (2026-11-17): verify does NOT auto-publish.

V1 RELEASE SCOPING — talent + feed + public profile only (2026-08-06, ADR-W, suite 624 green).
Config-driven feature flags gate whole product areas so production V1 ships ONLY the talent side, the public
feed, and the public talent profile; brand/contracts/messaging stay in the codebase, dormant.
- `config/features.php` → `brands`/`contracts`/`messaging`/`dark_mode`, each `env('FEATURE_*', true)` — **default ON** so
  dev/staging + the whole test suite run the full app. Production `.env` sets `FEATURE_BRANDS=false`,
  `FEATURE_CONTRACTS=false`, `FEATURE_MESSAGING=false`. Re-enable later = flip one env var (contracts/messaging
  imply brands).
- Enforcement is RUNTIME (testable via `config([...])`): `App\Support\Features::brands()/contracts()/messaging()`;
  `feature:<name>` route middleware (`App\Http\Middleware\EnsureFeature`, disabled → 404, alias in
  bootstrap/app.php); `@feature('brands')` Blade directive (registered in AppServiceProvider::boot). Routes STAY
  registered — the middleware 404s when off.
- Gated: brand dashboard group + public brand/opportunities/messaging routes (web.php); talent
  contracts+applications+enquire (talent.php); admin contract/flow/template consoles + brand/project moderation
  queues (admin.php); feed brand suggestions (feed.php). Nav arrays (public/talent/admin layouts + admin
  dashboard) carry a `feature` key and filter. Registration/login talent-only. Feed hides the BRANDS rail +
  brand follow chips + rebalances `.feed-grid` (`no-brand-rail`). Public profile hides the Message CTA.
  `config/feed.php` `allow_following_brands` = `env('FEATURE_BRANDS', true)`.
- `dark_mode` off (V1 light-only): the single `<x-theme-toggle>` component renders nothing (hides the toggle
  in every layout + the feed) and `partials/design-head` forces `data-theme="light"` (ignores stored pref).
- WATCH OUT: `@unlessfeature`/`@endunlessfeature` are NOT reliably registered by `Blade::if` — use
  `@feature(...) … @else … @endfeature` instead (a stray `@endunlessfeature` left a compiled `if` unclosed →
  500). Do NOT revoke `manage-flows` to hide contract admin — it is shared with Skills templates; gate by
  feature. KEPT in V1: Reviews (standalone, no brand/contract FK) + the talent "Brands I've worked with" block;
  admin stays fully functional (mandatory talent verification + feed moderation).
- Production seed: only Roles/TalentType/BlockType/Settings (skip demo + contract-template/flow + brand
  seeders); confirm `SettingsSeeder` tolerates a null `default_contract_flow_id` or keep `ContractFlowSeeder`
  (dormant). `tests/Feature/FeatureFlagsTest` proves the OFF gating.

TALENT DASHBOARD HOME REWORK (2026-08-10, suite 634 green, build green). The home KPI row is TWO cards:
(1) **Profile visibility** — inline public/private `role="switch"` reusing PATCH /talent/profile/publish
(unverified → 422 surfaced inline, switch disabled with an *Awaiting verification* hint); (2) **Profile
views** — lifetime total + rolling week-over-week delta, clickable (aria-expanded) into a column chart
with range pills 7d/30d/90d/**all** ('all' = since the profile's `created_at`). The Pending-reviews and
Blocks·skills cards are REMOVED. Time-series = NEW `talent_profile_views` daily buckets (unique
(talent_id, viewed_on)), written by `RecordDailyProfileView` (atomic ON DUPLICATE KEY UPDATE, MySQL-only)
on the existing `TalentProfileViewed` event — `view_count` stays the lifetime counter; NO backfill, the
series starts 2026-08-10. `ProfileViewStatsService` (read-only) owns weeklyComparison + series (buckets
day/week/month by span, zero-filled, labels localized server-side); endpoint GET /talent/dashboard/views
(ViewStatsRequest, bad range → 422). Front-end = `dashboardHome` in dashboard.js (chart is HTML/CSS
columns — accent bars, hairline gridlines, per-bar hover/focus tooltip, sr-only table; refetch dims the
old render in place; per-range cache). WATCH OUT: `foreignId('talent_id')->constrained()` inferred a
singular `talent` table in this repo — always write `constrained('talents')`; the half-applied migration
left an unrecorded table behind (create succeeded, FK alter failed).

FEED VOICE + TALENT TOPBAR (2026-08-11, suite 634 green, build green). Feed copy dropped the "light"
metaphor and now addresses the talent in second person (EN+AR): eyebrow "YOUR WORK · YOUR PEOPLE",
empty state "BE THE FIRST — SHARE YOUR WORK", end-of-feed "YOU'RE ALL CAUGHT UP", composer placeholder
"What are you working on? Type @ to credit someone." — CSS/JS mechanics keep light-y names
(.dl-light-veil, light-bar, in-the-light); copy only. The TALENT dashboard layout
(components/talent-layout.blade.php) is now a TOPBAR, not a sidebar: sticky header (row 1 = wordmark ·
divider · page title (sm+) · View profile/locale/theme/logout; row 2 = horizontally-scrollable pill nav
with icons, active = FILLED bg-accent+text-on-accent like the profile tab bar, aria-current="page");
no off-canvas drawer/backdrop/hamburger, body has no Alpine sidebar state, group headings flattened
(feature-gated Marketplace items still filter). Brand + admin layouts still use sidebars.
NAV IS STANDARDIZED (2026-08-11): `App\Support\Navigation::talentItems()` is THE canonical talent link
set (Dashboard · Feed · Discover · Activity · [flagged marketplace] · Profile · Content · Reviews) —
consumed by the talent-layout topbar AND by public-layout's topbar when auth('talent') (guests: Feed ·
Discover · Log in; brand/admin: Profile→dashboard). Add/rename a talent nav link THERE, never in a
layout. Public topbar active check is routeIs() and items may carry `params`.
ONE TOPBAR COMPONENT (2026-08-11, suite 636 green): `<x-talent-topbar :title>` is the single talent
chrome — talent-layout renders it, and public-layout swaps its fixed `site-topbar` for it when
auth('talent') (sticky, in-flow; body padding dropped). It is a SINGLE ROW (h-16 = the default
--topbar-h, so feed sticky offsets need no override — if the bar grows taller again, re-override the
token in public-layout): primary links are ONLY **Feed** + **Preview profile** (public profile,
_blank); EVERYTHING else (Dashboard · Activity · Profile · Content · Reviews · flagged marketplace ·
Log out) lives in the corner AVATAR DROPDOWN (Alpine-local open state, click-outside/Escape,
role=menu). NO locale switcher in it (V1 has no AR/EN toggle; /ar still routes). NEW `discover`
feature flag (config/features.php, FEATURE_DISCOVER — false in the V1 .env, pinned true in
phpunit.xml): /discover routes 404 behind `feature:discover` and the nav item drops from
Navigation::talentItems() + the guest topbar. V1 editor trims: booking_type/
booking_value are NOT editable (columns + public external-link rendering kept); the Profile editor
order is Publishing → Core → Pricing → **Blocks → Skills**, and in Blocks each skill's section renders
ONLY when selected in the pill bar (activeSkillTab in profileEditor; universal section always visible;
adding a skill auto-selects it; the scope card partial is talent/partials/block-scope-card.blade.php —
keep it the single card markup).
OFFICIAL FAMA BRANDING (2026-08-11, from the "FAMA - BRANDING PHASE 3" PDF in the repo root; suite
637 green; SUPERSEDES the same-day #BA49DA pass): primary **Orchid Purple #AD50D3** (accent; wash
#F7E0FF; accent-ink #8B2FA8), yellow **#FFEB73** = gold-weak (gold #7E6600), mint **#6CEAC2** →
success family (#0B7059 / #BDF4E3). Neutrals are the PDF's **WARM CREAM** — --bg #FAF6E0, --surface
#FDFBEF, warm lines, plum ink #231A25; dark theme = plum night (#171019 base). **BLUE IS BANNED**:
Daylight's --dl-base is the cream, --dl-daylight is deep orchid #8B2FA8 (was indigo), veil lavender,
shadows ORCHID-tinted ("warm ground, purple light" — the old blue-shadow rule is dead);
CheckFeedContrast's formula table + daylight.css @supports fallback hexes were regenerated together
(run `php artisan feed:check-contrast` after ANY daylight edit). GeneratesCoverImages seeds
brand-arc hues only, accent circles constrained to the ground's hue family (cross-family alpha
blends made cyan). The review-removed EMAIL inlines the brand hexes (emails can't read tokens).
Older "teal accent"/"cloud" wording below this line is historical. "+ Add skill" is a SOLID accent pill. The editor's
block scope cards have NO "Add a block…" picker and NO per-block ✕ remove — V1 blocks are the seeded
set (hide via the Visible toggle, reorder, move scope); the add/remove endpoints + ProfileBlockService
methods still exist (UI-only removal — restore the partial's rows to re-enable). NEW `pricing` feature
flag (FEATURE_PRICING — false in the V1 .env, true in phpunit.xml): editor section @feature-gated,
PATCH /talent/profile/pricing 404s, public profile rate chip suppressed via the $hasRate condition.
EDITOR CONSOLIDATED + BAR FINALIZED (2026-08-11, suite 636 green): the topbar's Feed + Preview links
sit on the START side next to the wordmark — NO page title in the bar (layouts still set <title>).
The feed's chrome header (t-display title + eyebrow + inline chips) is REMOVED — the Filters FAB is
the only filter surface and is ALWAYS visible (no pastHeader gate; feedStream's $refs.chrome uses are
null-safe). The profile editor page is titled **Edit profile** (nav label too); the old Publishing
section is a ROW inside the Core-details card. There is NO standalone Skills section — the skill
PILLS in Blocks are the manager: whole pill draggable (onSkillDrop({id})), ★ button = makePrimarySkill,
✕ = removeSkill (confirmed), dashed "+ Add skill" toggles showAddSkill picker inline.

UNIFIED DRAG LAYER (2026-08-11, suite 644 green): every draggable in the Blocks card (pills, skill
rows, block rows) goes through profileEditor.onRowDragStart/Over/Drop/End — dragging state
`dragging{type,key}`, hover `dragOverKey`, denial `denyKey` (600ms danger flash). Semantics:
block↔block same scope = onDrop reorder; block→section (row, pill, or expanded list) = dropMove →
moveBlock (moveTargets-gated); section↔section = onSectionDrop; section→universal block row =
onSectionDrop('universal') (crosses the group). dataTransfer.setData at dragstart is REQUIRED
(Firefox won't drag without it). Feedback classes live on the rows (:class arrays — scale/opacity on
source, ring-accent on target, ring-danger on deny; transition-all duration-150).
UNIFIED BLOCKS CARD (2026-08-11, suite 644 green, SUPERSEDES the All-pill pass): the editor's Blocks
area is ONE card — universal block rows + each SKILL as a single collapsed row (kicker "Skill
section", Primary badge, N-blocks count, "Manage blocks" expander revealing its rows inline), all in
true section order via profileEditor.unifiedRows (universal group spliced at universalPosition among
skill rows). Skill rows drag with onSectionDrop (same keys as the pills); block rows use the shared
partial talent/partials/block-row.blade.php (block-scope-card is DELETED). State: expanded{} +
toggleExpanded (activeSection/activeGroups are GONE); skill PILL click toggles that row's expansion;
there is NO "All" pill.
FEED WIDTH + REVIEWS + ALL-SECTIONS (2026-08-11, suite 644 green): the no-brand-rail feed spine is
BACK to `--dl-feed-col: 720px` (the 1000px V1 widening is dead — grid templates match). The talent
/talent/reviews page is READ-ONLY: no Approve/Reject UI (endpoints remain; admin queue moderates),
rating filter chips (All · 5★…1★) → `?rating=` on the data endpoint; crudList has `setQuery(q)` +
`activeQuery`. The editor's Sections bar has an **All** pill — activeSection 'all' (THE DEFAULT)
renders every section card stacked; pill click toggles focus⇄all.
ORDERABLE SECTIONS + PUBLIC HEADER (2026-08-11, suite 642 green): the editor's Blocks pill bar is ONE
orderable list — the UNIVERSAL section is a pill among the skills (card opens on select; open by
default; no star/✕) and dragging persists its slot to `talents.meta['universal_position']` via PATCH
/talent/profile/sections/universal-position. PUBLIC semantic (small ADR-R extension): position 0 =
universal region ABOVE the skill tabs (default), ANY later slot = BELOW the tab region (in-between
slots all mean below; the editor hint says so). Blade: profile.blade.php renders
talent/partials/universal-blocks in one of two spots keyed on $universalAfterTabs; `id=
"universal-blocks"` marks the region (tests assert order via strpos). JS: profileEditor.sectionPills/
activeSection/onSectionDrop (activeSkillTab is GONE). The public profile header is FULL-WIDTH
(max-w-6xl), single-row on lg (avatar · identity+location · stats · CTAs), bio on its own row below,
and a **Share profile** copy-link button (canonical url($slug), "Link copied!" state) sits beside
Leave a review — the old "…" menu was removed. WATCH OUT: the `hero` block type NEVER renders
publicly (ADR-O) — tests seeding a universal block must pick a non-hero universal type.

HERO BLOCK RETIRED (2026-08-12, suite 644 green): the `hero` block type is DEAD — migration
2026_08_12_000100 deletes every placement, strips the key from `talent_types.default_blocks`, and sets
`block_types.is_active = false`; TalentTypeSeeder no longer seeds it and BlockTypeSeeder seeds it
inactive (`'active' => false` entry key). The catalog row STAYS (keys are frozen) and the defensive
`key !== 'hero'` filters in profile.blade.php / PublicProfileResource / TalentProfileController stay for
un-migrated data. Tests must NOT use `hero` as a stand-in for "an active universal block" — use
`brand_collabs` (SkillCatalogService::updateDefaultBlocks now 422s on `hero` as ineligible).

TOPBAR LOGO + DASHBOARD QUICK LINKS + NAV RENAMES (2026-08-12, suite 647 green, build green): the
topbar shows the BRAND LOGO via `<x-fama-logo>` (inline SVG, brand hexes #B247D4/#FFE72F — NOT tokens;
the source `<style>`/.st0/.st1 classes were converted to fill attributes because those generic class
names leak) — rendered by `<x-talent-topbar>` and public-layout's guest `site-topbar`; auth
split-screens, admin/brand layouts, the footer and the review-removed e-mail still use the TEXT
wordmark (`Fama.`). `Navigation::talentItems()` items now carry a one-line **`description`**, and the
talent dashboard's "Where to next" grid renders THE WHOLE NAV SET (minus Dashboard) + a Preview-profile
card — add a nav item and it appears on the dashboard too. Labels: **Profile structure**
(talent.profile.edit) and **Profile content** (talent.content); **Reviews is NOT in the nav** (it is a
panel inside Profile content; /talent/reviews stays for the page + its data endpoint).

PROFILE CONTENT IS SECTION-FIRST (2026-08-12, suite 647 green, build green): `GET /talent/content/
{section?}` — `{section}` is `universal` or a SKILL SLUG (a legacy content-type key still resolves to
the section showing it; unknown → 404; no arg → first section). The page renders ONE section with ALL
its content blocks STACKED (`talent/partials/content-editor` per block, anchor `id="block-{key}"`);
the switcher is a row of premium section buttons (icon · name · Primary · count, active = filled
accent). The CRUD endpoints are UNCHANGED and still per type (`/talent/content/{type}/data|store|…`).
**Reviews are a panel here** — the list lives in `talent/partials/reviews-list` and BOTH
/talent/reviews and the Reviews block panel include it (one implementation). The type registry moved
to **`App\Support\ContentRegistry`** (add a content type THERE, not in the controller);
`ContentRegistry::keys()` gates the editor's "Edit content" link (`contentKeys` → profileEditor.
hasContentEditor/contentUrl in dashboard.js) so `comp_card` no longer offers a dead link, and the link
targets the block's own section + anchor. Block types with no editor are skipped when building panels.

PROFILE CONTENT SWITCHER (2026-08-12, suite 644 green, SUPERSEDED by the section-first pass above): the talent nav item `talent.content` is
labelled **Profile content** (change it in `App\Support\Navigation::talentItems()` — the avatar
dropdown and the public topbar both read it). /talent/content/{type} no longer shows a flat pill bar
of every content table: `BlockContentController::sections()` builds the switcher from the talent's
OWN `profile_blocks` — the **Profile-wide** section plus one section per skill (x-skill-icon + name +
Primary badge), each listing its table-backed blocks in block order, universal slot placed by
`meta.universal_position` (same order as the editor pill bar / public profile). Only block keys that
exist in the controller's registry become pills (so `reviews`/`comp_card` blocks are skipped and no
pill dead-ends); hidden blocks stay listed dimmed with a "Hidden" tag; a type opened by URL with no
block gets a trailing **Not on your profile** section. `$types` is gone from the view — use
`$sections`.

PROJECTS PAGE + AT-LEAST-ONE-SKILL (2026-08-18, ADR-X, suite 659 green, build green, migrate:fresh --seed
exits 0): the talent's portfolio projects live on their OWN page — `GET /talent/projects` (+ `/data`,
`POST`, `PATCH {project}`, `DELETE {project}`, `PATCH reorder`, `POST|DELETE {project}/cover`) — and a
project MUST carry at least one of the talent's own skills. New pivot `project_talent_type` (migration
2026_08_18_000100, unique (project_id, talent_type_id), `position` = tag order, backfilled from the old
column); `projects.talent_type_id` STAYS as the HOME tab and equals tag 0, so every ADR-Q reader is
unchanged. **The tab test matches the home column OR a tag** (`Project::belongsToScope()` /
`scopeInSkillScope()` / `scopeWithoutSkill()`) — the pivot only ADDS tabs, so a column-only row can never
fall off the public profile; a project tagged twice appears in BOTH tabs. The rule is enforced in
`TalentProjectService::assertOwnedSkills()` (422) AND `StoreProjectRequest` — never reimplement it.
WATCH OUTS: (1) `ProjectFactory::configure()` mirrors a factory-set `talent_type_id` into the pivot — a
MODEL hook would NOT work because DatabaseSeeder uses `WithoutModelEvents`; tests must therefore NOT
manually attach the home skill again (duplicate-key 1062) — attach only the EXTRA tags. (2)
`SkillsService::removeType` now calls `untagProjects()`: drop the tag, re-home on the next remaining tag,
un-scope only what ran out (ADR-Q content preservation intact). (3) **`projects` was REMOVED from
`App\Support\ContentRegistry`** — ONE write path. The profile-content page renders a `link`-kind signpost
panel instead of a second editor, `/talent/content/projects` REDIRECTS to the Projects page, and
`contentUrl()` in dashboard.js special-cases the key; the dead `skill_scoped` machinery in
`BlockContentController` + the generic editor partial is gone. (4) The composer modal reuses `filterModal()`
(scroll lock + focus trap; NO x-transition on the teleported node) and its `form` is seeded in the data
factory, NOT `init()`, because a teleported template is parsed on first paint. (5) Runtime JS strings come
from Blade via the `strings` option — the dashboard has no `window.famaLang`. `Projects` is in
`Navigation::talentItems()` so it shows in the avatar dropdown AND the dashboard "Where to next" grid; the
WYSIWYG is now the shared partial `talent/partials/rich-input` (include with `['model' => 'form.summary.en']`).
Reorder is offered only on the unfiltered single-page list (a partial list cannot express an order).
V1 TRIMS on the composer (2026-08-18): it is ENGLISH-ONLY — the Arabic title input is COMMENTED OUT with the
same note profile-editor.blade.php uses for its AR headline/bio (that is the house pattern for V1 AR trims:
comment the input, keep the `.ar` key in the payload). WATCH OUT: never let a hidden locale submit an empty
string — `TalentProjectService::dropEmptyForeignTranslations()` strips blank NON-ENGLISH locales before
storage, because spatie returns a stored `''` instead of falling back (a blank title on /ar); spatie's
setTranslations() MERGES per locale, so a previously stored AR value survives an English-only edit, and a
blank EN value still clears (that input is on screen). The COVER IS OPTIONAL — no rule in
StoreProjectRequest, not part of `canSave`, tagged `Optional` in the UI with a placeholder-fallback note.

BLOCK NAMES ADMIN-GOVERNED (2026-08-13, suite 647 green, build green): talents can NOT rename blocks —
the editor block row shows a read-only name (`title` fallback to catalog `block_types.name`),
`saveTitle` is gone from dashboard.js, and `FillBlockRequest` has NO `title` rules (submitted titles
are silently ignored by validated(); content/settings/layout still accepted). Naming lives with the
admin block catalog (/admin/blocks). ProfileBlockService::fillBlock still handles `title` internally
(seed/admin paths) — the gate is the Form Request boundary.

Phase 4 (mobile API: Sanctum + Scribe over existing services) is deferred; Prompt 5K mirrors the feed
endpoints under /api/v1 with Sanctum and starts docs/api.md regeneration.

SKILLS NEST INTO SPECIALITIES — CLIENT SPEC PHASE 1/3 (2026-08-22, ADR-Y; suite 677 green / 2464
assertions, build green, migrate:fresh --seed exits 0). Implements the client's three structure specs
(`profile.html`, `project.html`, `spcialist.html` in the repo root — they are SPEC DOCUMENTS, not
designs: their paper/blue/Sofia-Condensed styling is the document's dress, NOT a brand restyle; the app
keeps the Orchid branding). Phase 1 = the taxonomy everything else hangs off.
- **Nine skills** (was six): added **Direction** (`crew` — the spec says it mirrors cinematography, so
  same category, same eligible blocks), **Hair & makeup** (`crew` — a HMUA books with a kit = the
  equipment block), **Retouching & post** (`creative` — the software stack is the proof). Migration
  `2026_08_22_000200` renames in place ("Modeling" → **Modelling**, title-case → sentence-case) and
  inserts the three; `TalentTypeSeeder` seeds the same nine. **SLUGS ARE UNCHANGED** — `modeling` stays
  `modeling` because the slug is the public deep link (`?skill=`, `filter[type]=`) and ADR-S already
  broke those once.
- **NEW second level: `specialities`** (migration `2026_08_22_000100`) + `speciality_talent` +
  `project_speciality` pivots. 45 seeded entries, 4–7 per skill (`SpecialitySeeder`, runs right after
  `TalentTypeSeeder` in `DatabaseSeeder` — specialities nest under skills, so the catalog must exist
  first). Model `Speciality` (`$table = 'specialities'`, `scopeSelectable()` = `is_active`).
- **`slug` is unique PER SKILL, not globally** — "Fashion" lives under photography, modelling AND
  styling, and that overlap is the feature (one search assembles a whole crew). Never add a global
  unique index on it.
- **THE VOCABULARY IS CONTROLLED**: pickers only, no free-text field anywhere. Two levels is the whole
  tree. Every entry answers ONE question — what kind of SUBJECT; output format (campaign, editorial,
  lookbook, e-commerce) belongs to the project and stays out.
- **TWO RULES, ONE HOME EACH — never reimplement them.** A talent's picks must belong to a skill they
  hold: `SkillsService::setSpecialities()` (per-skill replace, so one skill's picker can't wipe
  another's; empty list is a legitimate clear). A project's picks must hang off one of its TAGGED
  skills: `TalentProjectService::assertSkillSpecialities()`, validated against the skill set the project
  will END UP with — `update()` with new `skill_ids` but no `speciality_ids` calls `pruneSpecialities()`
  rather than orphaning them.
- **Retiring ≠ deleting** (ADR-T's grandfathering, applied to the vocabulary): `is_active = false`
  removes an entry from every picker while existing claims keep rendering and keep being found. The
  **slug is LOCKED** once created — renaming would silently drop every talent and project matched
  through it. Admin: `/admin/skills` (`manage-flows`) is the only way an entry is born.
- **Removing a skill DETACHES its speciality claims** — deliberately NOT ADR-Q's preserve-the-content
  rule, because a claim carries no content and an orphan would keep the talent in a discovery filter for
  a skill they no longer hold.
- `Speciality` needs its **`speciality` MorphMap alias** (the admin catalog writes it to the activity
  log) — as does any new model touching a morph column, or writes throw ClassMorphViolationException.
- Surfaces: profile editor (pill picker inside each skill section, optimistic, `PUT
  /talent/profile/skills/{type}/specialities`); public profile (`talent/partials/skill-blocks` leads
  with the skill's specialities — it takes an optional `$skill`, and BOTH the server-rendered active tab
  and the lazy `/{slug}/tab/{skill}` endpoint pass it; the discovery link is `@feature('discover')`-gated
  so V1 renders a static pill, not a link to a 404); projects composer (`specialityGroups()` reveals
  groups BY the tagged skills, `toggleSkill` calls `pruneSpecialities`); admin `/admin/skills`.
- Discovery: `filter[speciality]` (slug-matched). Combine with `filter[type]` to narrow.
- **WATCH OUT — two latent test fragilities this exposed** (fixed at the root, worth knowing the shape):
  an assertion comparing a **sorted actual against an unsorted expectation** only passed while modeling
  held a lower id than photography (the seeder order changed), and one comparing a **raw skill name
  against escaped HTML** broke as soon as a name contained `&` ("Hair & makeup" → `Hair &amp; makeup`).
  Sort both sides; wrap name expectations in `e()`.
- **STILL TO COME (phases 2 and 3 of the client spec)** — not built yet:
  - *Portfolio* (`profile.html`): the six fixed sections. **Now** (`based_in` ✓ exists as `base_city`,
    but `will_travel` + `available` toggles must come BACK — ADR-L dropped them — with a visible
    last-changed date), **About** (`worldview` and `social` are new fields), **Recognitions** (NEW —
    invited peer vouches, must look visibly different from reviews), **Willing to explore** (NEW —
    structured interests tied to a skill so brands can filter, not free text).
  - *Project* (`project.html`): **Collaborators** is the big one — a credit is `person + skill + role`,
    needs the taggee's CONFIRMATION, and writes onto their portfolio. Plus `date` stored-but-not-shown
    (`year` exists; the spec wants a real date, hidden from the page).

PORTFOLIO = SIX SECTIONS — CLIENT SPEC PHASE 2/3 (2026-08-22, ADR-Z; suite 695 green / 2522 assertions,
build green, migrate:fresh --seed exits 0, all six sections verified rendering live). Implements
`profile.html` ONTO the existing block system (the stakeholder call): sections that describe the PAGE
live in the header (Now, About); sections that are CONTENT are blocks (Reviews, Recognitions, Willing to
explore).
- **NOW** (migration `2026_08_22_000300`): `talents.will_travel` + `is_available` +
  `availability_changed_at`, rendered as one strip at the top of the public header and as two
  save-on-flip toggles in the editor (no Save button — this is what goes stale fastest).
  **SUPERSEDES the availability half of ADR-L and is NOT a revert** — ADR-L dropped an
  `availability_status` ENUM + `willing_to_travel` + `travel_regions` JSON; these are NEW columns with
  simpler semantics. ADR-L's other half STANDS: **availability is INFORMATION, never a gate** (never
  blocks an enquiry; only narrows discovery when a brand asks). `will_travel` is a YES/NO, never a list
  of cities.
- **WATCH OUT — the `availability_changed_at` stamp:** it moves ONLY when availability actually flips.
  Never compare the incoming value against `$talent->is_available` by hand: on a model whose attribute
  was never hydrated (fresh insert relying on the column default, a `select()`ed subset) it reads NULL,
  `(bool) null` is `false`, and flipping to "not available" compares `false !== false` and SILENTLY SAVES
  NOTHING. Assign first, then let `isDirty()` decide. `TalentFactory` now sets both toggles explicitly so
  a factory model matches a real row.
- **ABOUT**: `talents.worldview` (translatable) is taste + point of view, NOT a second bio — bio is
  background/track record, and the editor PROMPTS worldview with specific questions because an empty box
  beside Bio just gets a second bio typed into it. `talent_social_handles` mirrors `brand_social_handles`
  (whole-list replace via `PUT /talent/profile/social`; UNIQUE(talent_id, platform), 422 on a duplicate).
  **`UpdateCoreProfileRequest` must list `worldview`** or `validated()` silently drops it.
- **RECOGNITIONS** = peer vouches. **Reviews are BRAND → talent (after a booking, so impossible at
  launch); recognitions are TALENT → talent (invited, no booking, so they work from day one).** Same
  admin moderation queue, `RecognitionState` identical to `ReviewState`, `is_approved` synced by
  `SyncStateProjections` (never set by hand). Written at `GET|POST /{slug}/recognise` (talent guard —
  guest → talent login as intended URL; own profile → redirect). `UNIQUE(talent_id, author_talent_id)`:
  a peer vouches ONCE and re-submitting REPLACES + returns to pending (an approved vouch that is reworded
  must not stay public on the old decision). They MUST render differently from reviews — no stars,
  quote-led, relationship first — or the page is one wall of praise and neither gets read.
  Admin: `/admin/moderation?queue=recognitions`.
- **WILLING TO EXPLORE** = `talent_interests` (skill + OPTIONAL speciality + note, capped at 6).
  Structured, never free text (as prose it reads as a mood and is unfilterable). **The skill is
  deliberately NOT restricted to ones the talent holds** — naming a direction you have not worked in yet
  is the whole point; only the speciality is checked (must belong to the chosen skill).
  `TalentInterestService` is the only write path.
- **PROFILE-LEVEL BLOCKS ARE DATA (new column `block_types.is_profile_default`):**
  `talent_types.default_blocks` seeds into ONE SKILL'S TAB (ADR-Q) — right for a gallery, wrong for a
  section describing the person, which would repeat once per tab. Flagged blocks are seeded ONCE at
  `talent_type_id = NULL`. Never hardcode that list in a seeding action.
  **WATCH OUT:** `SeedBlocksForSkill` now returns blocks in TWO scopes (skill tab + profile defaults) —
  two old tests encoded "every seeded block belongs to a skill / zero universal blocks" and were updated
  to derive the expected count from the catalog. Hand-rolled demo seeders read `default_blocks` (skill
  blocks only), so they MUST use `Database\Seeders\Concerns\SeedsProfileDefaultBlocks` or the sections
  exist in the catalog, exist in the data, and render NOWHERE.
- Discovery gains `filter[available]`, `filter[will_travel]`, `filter[open_to]` (skill slug of a stated
  direction — the one filter that surfaces someone who WANTS the work but has not done it).
- New MorphMap aliases: `recognition`, `talent_social_handle`, `talent_interest`.
- Phase 3/3 (`project.html`) landed the same day — see ADR-AA below.

PROJECT = CLIENT · DESCRIPTION · MEDIA · COLLABORATORS · SKILLS · DATE — CLIENT SPEC PHASE 3/3 COMPLETE
(2026-08-22, ADR-AA; suite 711 green / 2573 assertions, build green, migrate:fresh --seed exits 0,
credit loop verified live in both directions). **The client's three structure specs (`profile.html`,
`project.html`, `spcialist.html`) are now fully implemented.**
- **COLLABORATORS** (`project_credits`, migration `2026_08_22_000700`) = `person + skill + role`. Skill
  says what someone CAN do; role says what they did HERE. **TWO KINDS, and the difference decides
  everything:**
  - **ON-PLATFORM** (`talent_id`): tagging writes the project onto THEIR portfolio, so it **REQUIRES
    THEIR CONFIRMATION** — `pending`, invisible EVERYWHERE (public project page included) until
    accepted. This is the whole safeguard; without it anyone could attach a name to work they did not do.
  - **OFF-PLATFORM** (`name` only): `listed` immediately. Nobody to ask, no portfolio to write to, never
    leaves this project — so nothing can be claimed on anyone's behalf.
  - `accepted → declined` IS allowed: declining and withdrawing are the same transition, because being
    publicly associated with a project stays the collaborator's call at any point.
  - **Owning a project and being named on a credit are SEPARATE authorisation questions.** Owning never
    lets you answer for someone else (403); being credited never lets you edit someone else's block.
  - An accepted credit files the project under the **CREDIT's** skill, not the project's own (you can be
    credited as a stylist on someone else's photography job).
- **WATCH OUT — accepting must ensure a `projects` BLOCK exists in the target scope.** A profile only
  renders projects where such a block exists (ADR-Q), and several skills seed a gallery and NO projects
  block (photography). Without `ProjectCreditService::ensureProjectsBlock()` the collaborator confirms a
  credit and sees nothing — the promise is a lie. **Any path writing `accepted` straight to the column
  (a seeder, a fixture) SKIPS that** and produces demo data where a confirmed credit renders nowhere;
  go through `accept()`. Also: a credit may name a skill the collaborator does NOT hold (the owner picks
  it) — those fall back to the universal region instead of vanishing into a non-existent tab.
- **MEDIA**: a multi-file `media` collection (images + video) beside the single-file `cover`. **The
  ORDER is the edit** — first frame = the lead, chosen not defaulted. Uploads APPEND (medialibrary
  stamps `order_column` = max+1 itself, no manual ordering). `Project::orderedMedia()` +
  `lead_image_url` (cover → first media item).
- **DATE**: `projects.occurred_on` (migration `2026_08_22_000600`, indexed with `talent_id`) is
  **RECORDED, NOT DISPLAYED** (SUPERSEDED 2026-11-04, ADR-AD: the YEAR now shows as the tag via `ProjectTitle::forProject`; the full date still never shows) — it orders work by recency and answers "shot recently", and appears
  NOWHERE public, so an older strong project is not discounted for its age.
- **WATCH OUT — `year` is now a DERIVED PROJECTION and was REMOVED from `$fillable`.** It is written
  only by `Project::setOccurredOnAttribute()` (a MUTATOR, not a model event, so it survives
  `WithoutModelEvents` seeders and factories). It stayed fillable at first and the demo seeder set
  `year` while the factory set `occurred_on` — they silently disagreed (2022 vs 2025). A projection
  anything can write is not a projection. With `preventSilentlyDiscardingAttributes` on, mass-assigning
  `year` now THROWS. `StoreProjectRequest` no longer accepts it either.
- **CLIENT** is hidden when empty — no "personal project" placeholder, no empty label; `filled()` gates
  it on both the project page and the card.
- `Talent::portfolioProjects(?int $scope)` is THE portfolio view (owned in scope + accepted credits,
  de-duplicated); the public projects block and the profile's project count both read it so the count
  matches what the page shows.
- New: `/talent/credits` (confirm/decline/withdraw, in `Navigation::talentItems()`), credit
  notifications in `App\Notifications\Credits\*` reusing the FEED payload shape so they land in the ONE
  inbox, MorphMap alias `project_credit`, `ProjectCreditState` (pending/accepted/declined + terminal
  `listed`).

OUTGOING MAIL (2026-09-22, suite 717 green): ZeptoMail SMTP, fully env-driven (Laravel 13 — `MAIL_SCHEME`
null on 587 = auto-STARTTLS; there is no MAIL_ENCRYPTION). From/Reply-To go through named SENDER PROFILES
(`config/mail.php` `senders`: system/support/info/contact) via `App\Mail\MailSender::envelope()` /
`::apply()` — never hardcode an address, never add a global `mail.reply_to` (Laravel appends it, doubling
Reply-To). Hosts that block SMTP use `MAIL_MAILER=zeptomail` — `App\Mail\Transport\ZeptoMailTransport` (HTTPS API, same token as MAIL_PASSWORD; `MAIL_TIMEOUT` caps SMTP hangs). Mailables are `ShouldQueue` + `afterCommit()` (assert with `Mail::assertQueued`); `TestMail` is
the deliberate sync exception behind `php artisan mail:test {to} [--sender=] [--queue]`.
EMAIL DESIGN (2026-09-22, suite 721 green): ONE branded shell for ALL mail — `<x-email.layout>` (+ button/
callout/panel in components/email), brand hexes/fonts ONLY in `App\Mail\EmailTheme` (mirrored by
vendor/mail/html/themes/fama.css for markdown mail). Framework notifications reach it through the published
`resources/views/vendor/mail/html/*` overrides — `config('mail.markdown.paths')` MUST list that dir or they fall
back to stock Laravel. WATCH OUTS: tables with a radius need `border-collapse:separate`; never put `align` on
the button's own table (it floats); dark-mode button rules must stay AFTER the generic `a` rule; the logo is a
PNG (public/images/email/fama-logo.png — no SVG in Gmail/Outlook); new email copy → lang/ar.json; the admin
review-delete live preview hand-mirrors emails/review-removed.

FAMA-UI DESIGN ADOPTED (2026-10-12, ADR-AB; suite 787 green; :first-child rules are re-targeted after <template> in app-bridge.css, build green, migrate:fresh --seed exits 0).
The client's static prototype (`fama-ui`, reference copy in ~/Downloads/fama-ui) IS the UI for talent, feed,
public profile, auth and error pages. SUPERSEDES the Orchid/Daylight styling notes above for those pages
(brand/admin/contract surfaces stay on Tailwind app.css).
- Shell: `<x-fama.layout :title mode="talent|public|auth|bare" :css="['posts',…]">` loads ONLY
  `public/fama-ui/css/{shell,ds,<extra>}.css` + `app-bridge.css` + `@vite('resources/js/app.js')` — NO app.css,
  so use prototype classes, never Tailwind utilities, on these pages. Page styles = the prototype's <style>
  verbatim in `public/fama-ui/css/pages/<page>.css`. Pieces: `components/fama/{nav,hero,footer,icons,
  confirm-dialog,toast}`, `partials/fama-head` (locale prefix + label maps), `public/fama-ui/js/hero.js` (star
  photo banner). JS per area in `resources/js/ui/` (shell, feed*, profile*, projects, auth). Error pages are a
  STANDALONE layout (no Vite/Alpine/DB).
- WATCH OUTS: (1) ds.css `[hidden]{display:none!important}` beats x-show — never put `hidden` on an x-show
  element, use `x-cloak`. (2) Alpine CALLS a function-valued expression: bind `x-show="!!fn"`, never a bare
  function (the toast Undo fired instantly). (3) `$confirm` still works (native `.dlg` on the same store);
  toasts = `window.famaToast(msg, undoFn)`. (4) The prototype's JS is fake data — never ship it. (5) Visitor-first actions: `window.famaSignedIn` (fama-head)
  is checked BEFORE any optimistic flip — use `asVisitor(verb, request)` (ui/feed-util) so a guest's button never
  flashes "Following"/liked before the sign-in dialog (the request still runs so the 401 captures the intent).
  (6) Tailwind classes (e.g. `hidden`) do nothing on fama-ui pages — an SVG sprite "hidden" that way renders as a
  300×150 box; hide sprites with width/height 0 + position:absolute. (7) The prototype defines `i-msg` twice: the
  sprite's `i-msg` is the posts.js chat bubble; preview.html's square-plus is `i-msg-plus`.
- Talent nav (SUPERSEDED 2026-11-04 by the side bar — see the sync block below): Feed · Activity · Profile structure · Projects · Credits (+ Preview, bell → `GET /notifications/all`
  page `notifications.page`). `talent.dashboard` REDIRECTS to `talent.profile.edit` (dashboard view deleted).
- SITE ROOT: `/` → `/feed` when the viewer's candidate list is non-empty, else login (signed-in → dashboard);
  `FeedController::home` (2026-10-15, suite 789 green).
- VIEWS CHART: no table toggle; bars grow in via a CSS height transition (`grown` flips after rAF + setTimeout
  fallback, per-bar `transition-delay` capped at 350ms) on load and every range switch.
- LOCATION DROP LISTS (2026-10-15, suite 798 green): Country/City are `<x-fama.combo>` (searchable; behaviour
  `ui/combo.js` `famaCombo`). It binds via `get`/`set` closures on the PARENT model — NOT x-modelable (its entangle
  dropped updates when two combos changed in one tick, e.g. Discard). `App\Support\Locations` = THE country/city
  source (resources/data/locations, GeoNames CC BY; flags public/fama-ui/img/flags, flag-icons MIT); `base_country`
  stores the canonical English name (aliases canonicalised in UpdateCoreProfileRequest::prepareForValidation; a
  legacy unknown value may ride along unchanged); City accepts free text. Don't name a class `.ph` on fama-ui pages
  (ds.css `.ph` is a grey placeholder pill). Profile structure save bar is hidden when clean (`barShown`).
- FEATURED PROJECT (2026-10-15, suite 809 green): `talents.featured_project_id` via `FeaturedProjectService` (THE
  home — feature/ensure/resolve): always exactly one featured when the talent has projects, never un-featured, an
  only project is locked; only HOME-owned projects can be featured. Any new path that creates/deletes/transfers a
  project must call `ensure()`.
- STEPS-DONE EMAIL: `ProfileCompletionService::notifyIfComplete()` after any write that can finish a step (sends
  once, guarded by an atomic `steps_completed_at` UPDATE; skipped for verified/Live). A new step-completing write
  path must call it.
- ASSET VERSIONS: link fama-ui CSS/JS with `\App\Support\FamaAsset::url('fama-ui/…')` (mtime version) — never a
  hardcoded `?v=`. CSS load order: page sheets load AFTER app-bridge.css, so a bridge override of a page rule needs
  higher specificity.
- ADMIN UI (2026-10-15, suite 818 green): admin = Tailwind + `[data-surface="fama-admin"]` token re-read
  (`resources/css/fama-admin.css`) — SUPERSEDES the Orchid/cloud admin look; light only, no theme toggle, no
  locale switcher in the admin header. Staff login = x-fama.layout mode="bare" + auth.css, ink showcase.
  Verify/Suspend → `openNotice()` composer; email copy/preview/send live in `AccountNoticeService` (preview renders
  the real `emails/account-status` view). New decision emails: extend `AccountNoticeService::TYPES` + defaults().
  Dashboard insights = `AdminInsightsService` (one grouped query per series, zero-filled; day/week/month buckets).
  'Verified' over time reads the activity log (`talent.verified`) — talents have no verified_at column.
- MEDIA RETENTION (2026-10-15): hard deletes remove files at once (medialibrary deleting hook — always delete
  media-holding models through Eloquent `->delete()`, never a query-builder delete or a DB cascade, or files orphan);
  soft deletes keep files for `media-library.trash_retention_days` (30) then `media:purge-trashed` (daily) removes
  them and sweeps orphans. Posts: a soft delete cascades to comments (PostPurger via PostObserver); past the window
  the post is purged for good (`PostPurger::purge` — polymorphic likes/mentions/links/bookmarks first, then forceDelete).
- TRASH: `TrashService` is THE home for restore / delete-forever rules (post restore brings its comments; talent
  purge takes their whole footprint, hands co-owned projects over). The daily purge calls it for talents past the window.
- ADMIN BULK: `ModerationBulkService` (delete via each row's audited path; CSV export with formula-injection guard).
  Staff profile preview: `TalentProfileController::isStaff()` lets moderate-content admins open private profiles.
- LOGIN LANDING (SUPERSEDED 2026-11-04, ADR-AC — see the sync block below: done profile → Feed, else Profile structure; registration still Profile structure). Old rule: a talent login (and talent registration) ALWAYS lands on `talent.profile.edit` — the visitor's
  pending feed action is still replayed by the Login event, but `feed.return_to` is discarded (copy says "we'll
  finish it as soon as you're in", never "bring you back"). Brand/admin keep route('dashboard').
- PRODUCT RULES (stakeholder, must not change): no dashboard; NO PRIMARY SKILL — order = tab order, first opens
  first, `is_primary` is derived from position 0 by `SkillsService::syncPrimaryFromOrder()` (setPrimary = move
  to front); Private profiles show NO link (only the status); Profile structure is DRAFT + bottom save bar
  (Discard has Undo; avatar + publish stay immediate); NO REVIEWS — `features.reviews` (default false) hides
  them, Recognitions replace them; CO-OWNERS — an accepted on-platform credit = co-owner; any owner edits or
  leaves (`POST /talent/projects/{project}/leave`; creator exit transfers to the earliest-confirmed co-owner,
  tags re-homed to the heir's skills); only a SOLE owner deletes (422 otherwise). One home:
  `Project::isOwnedBy/isSoleOwner/ownerIds` + `ProjectPolicy`; skill tags validate against the HOME owner.
- Project editor is a PAGE (`talent.projects.new` / `talent.projects.edit`): publish creates, then autosave
  (700ms). Results = ≤4 `{label,value,compare}` rows (`Project::resultRows()`; migration 2026_10_12_000300).
  On-platform credits are picked via `/mentions/search`.
- Feed: ONE card `feed/partials/post` (page 1, /feed/items html[], fragment, Activity); runtime strings via
  `feed/partials/lang` (`window.famaLang`, `window.famaMe`) — daylight-head is no longer used by the feed;
  `#SkillTag` → `App\Support\SkillTags`; brands rail removed from the feed. Live /feed is empty in dev when
  every post is older than the ranking windows (data, not a bug).
- PASSWORD RESET is per account type (fixed 2026-10-12): `App\Auth\PasswordBrokerResolver` picks the broker
  from the email (talent → brand [flag] → admin user; `role` hint on the form wins when that account exists);
  reset links carry `account=talent|brand|user` (ResetPassword::createUrlUsing in AppServiceProvider) and
  NewPasswordController resets through that broker. Each broker has its OWN token table
  (`talent_password_reset_tokens`, `brand_password_reset_tokens`, framework `password_reset_tokens` = admins).
- DEMO DATA: `ScreensDemoSeeder` (last in DatabaseSeeder, idempotent — `php artisan db:seed
  --class=ScreensDemoSeeder` tops up a live dev DB) fills every screen for talent-demo@fama.test (120 days of
  views, socials, 3 rich projects with galleries/Results/pending+listed+co-owner credits, 2 credits waiting,
  6 recognitions incl. pending, mutual follows, saved posts, 9 notifications; its covers/galleries/digitals/
  post photos are the PROTOTYPE's sample photos from public/fama-ui/img/banners, avatar removed → initials, as in
  preview.html) + a Private/unverified
  `talent-new@fama.test` (password `password`) for the gate states. `php artisan demo:freshen` slides feed
  timestamps forward when the dev feed has aged past the 30-day ranking windows (flushes the cache; refuses in
  production without --force). WATCH OUT: dev .env uses real SMTP — auth flows send real mail.
- Profile-content SELECT fields (equipment category, gallery media_type, digital shot_type, showreel platform,
  software proficiency) are NOT NULL enums with defaults: `BlockContentController::dropBlankSelects()` omits a
  blank select so create takes the default and edit keeps the stored value (was a 500 — fixed 2026-10-12).
- ADMIN ON PUBLIC PAGES (2026-10-27, suite 863 green): the admin guard is NOT a feed actor but IS signed in —
  `x-fama.nav` public mode shows the staff account (Admin panel + Log out) and `window.famaStaff` makes every
  visitor gate (`famaSignIn`, `asVisitor`, profile follow) toast "read-only for staff" instead of a sign-in prompt.
  No emoji as UI icons anywhere (use text tags or inline SVG).
- KNOWN GAPS (see the 2026-10-12 report): prototype-only features
  with no backend (41-skill taxonomy, per-skill practice fields, LinkedIn-style experience, repost, project
  drafts/free-form blocks/video embeds, hiding single credits/recognitions, recognition notifications).

PROTOTYPE SYNC TO 1 NOV 2026 (2026-11-04, ADR-AC + ADR-AD; suite 897 green, build green, migrate:fresh --seed
exits 0). The co-founder's prototype (https://fama-ui.vercel.app) is THE UI; they date every change in a source
comment — sync by mirroring the site and listing dated comments newer than the last sync (method in ADR-AD).
- SHELL: signed-in talent = SIDE BAR ≥1101px (`html.fx-has-side`, folded rail by default, set before paint in
  `<x-fama.layout>`; `localStorage['fama-side-folded']`), the page is a rounded panel. Pieces in
  `components/fama/nav.blade.php` + partials `components/fama/partials/{nav-account,nav-notifs}`; behaviour
  `famaSide` / `famaNav` in resources/js/ui/shell.js; notifications for BOTH bars = ONE store
  `Alpine.store('famaNotifs')` (pages sync the badge through it, never via a component's $data). Create menu →
  `talent.projects.new` / `feed?new=post` (feedComposer opens on `?new=post|photo` and strips it). Top bar
  ≤1100px order: Projects · Edit profile · Credits · Feed · Activity (portfolio first, ADR-AE). Nav label is "Edit profile". The Private
  notice shows only while `! is_published` and never on Edit profile (its own floating tracker replaces it).
  WATCH OUT: shell.css margins key off `.fx-nav + main` / `.fx-nav + .fx-hero-wrap` — nothing may sit between
  the header and the page's first block (the owner's `.pv-strip` is the one allowed exception).
- GO LIVE (ADR-AC): `ProfileCompletionService` is THE home — Go Live = display name · a speciality · city, and a
  profile goes Live BY ITSELF when they are saved (`App\Actions\Talent\SyncGoLiveState` via `syncAfterWrite()`);
  Stand out (7, advisory) — `show` lists a Live profile in the Creatives Index (`isListed()`); `isProfileDone()` =
  all ten → login and `/` land on PROJECTS (`homeRoute()`, ADR-AE — was the Feed). Unpublished/Suspended are holds a save never undoes.
  `is_verified` = badge only. Any new write path that can complete/break a Go Live step must call `syncAfterWrite()`.
- LOGOUT (fixed 2026-11-04): `POST /logout` is `auth:talent,brand,admin`. NEVER put a shared-auth route
  behind plain `auth`/`guest` — they check only the default guard (`admin`). The other Breeze `auth`
  routes (verify-email, confirm-password, password update) still have that shape.
- YEAR TAG: `App\Support\ProjectTitle::forProject()` (title year, else `projects.year`) — the one home.
- Public profile: featured-project hero, profile-wide sections ALWAYS after the tabs (universal_position retired
  in the UI; endpoint kept), not-live page for private profiles (still 404). GSAP 3.12.5 + ScrollTrigger from
  cdnjs on the profile and project pages (`public/fama-ui/js/{profile,project}-motion.js`).
- Project page = editorial case study (`project-page.css`); the editor IS the project page
  (`project-editor.css`, `famaProjectEdit`); its bar docks before `<main id="page">` on wide screens and is
  teleported into `.fx-nav > .fx-in` on narrow ones.
- Notifications page = cards (`NotificationInboxService`, `meta.counts`, resource `kind/excerpt/followed_back`);
  Activity opens on Saved. Toast stays while hovered; Alt+Z = Undo.
- OPEN for stakeholders: AR translations for the ~200 new strings (Arabic is paused in the prototype too); the
  prototype's locked link on a Private profile (app keeps "no link while Private"); Discovery not yet limited
  to `isListed()` (one line, when `features.discover` turns on). Not ported (no backend): free-form project
  blocks, repost, LinkedIn-style experience, per-skill practice fields, hiding single credits/recognitions.

PROTOTYPE SYNC TO 13 NOV 2026 (2026-11-17, ADR-AE; suite 902 green, build green). Source: the client's
"FAMA-update-2026-11-13-b.pdf" (items 15–18) + every 12–13 Nov dated comment in the prototype.
- SIDE BAR: groups Your work (Projects · Credits) → Your profile (Edit · View) → Community (Feed · Notifications ·
  Activity). Folded rail = icons only; each link carries `data-tip` (the CSS tooltip) — keep it on new links.
- PROJECT PAGE: the team is listed as EQUALS — owner + confirmed + off-platform credits: cover "By A, B and C"
  (stacked `.avs`) + the "Made by" band (`.hire.team` > `.tm`, name + role). The separate Credits list, "Open for
  work", the phone `.hirebar` and its JS are gone (no hiring on Fama yet). Owner: "See it as a visitor"
  (`?visitor=1` → rendered as a non-owner) + Edit. Visitors: Share + Follow (the prototype's Like has no
  backend — project likes are not ported). Picture numbers show only in the full-screen viewer.
- PROJECTS LIST: one action per card — cover opens the project, Edit is the only button. Delete / Leave live only
  in the editor's Settings (OPEN: stakeholders asked where they should live).
- FEED: every inline comment takes a Reply (`openReply/cancelReply/sendReply/retryReply` on postFrame; markup
  `feed/partials/comments` + `feed/partials/comment-item`); one level deep (CreateComment re-attaches deeper
  replies). Post cards: media first, then text, then link chips.
- EDIT PROFILE: no skill reordering (drag/arrows gone); "Make primary" moves a skill to the front
  (`makePrimary`) — is_primary stays derived from position 0. Tracker = two labelled groups ("1 · Go Live n/3",
  "2 · Stand out n/7"), no Index milestone. Status pills solid. New projects open with the Photos block shown.
- PUBLIC PROFILE: point of view (worldview) moved into the hero (`.ct-pov`), out of "About {name}". The owner's
  strip is a lavender band with a real Edit-profile button.
SKILLS CATALOG = THE PROTOTYPE'S 41 (2026-11-17, ADR-AF; suite 902 green; fresh migrate+seed exits 0). THE catalog
is `database/data/skill-catalog.php` (41 skills, 195 specialities, identical to fama-ui skills.js) applied ONLY by
`App\Actions\Catalog\SyncSkillCatalog` (seeders + migration 2026_11_17_000100). Edit the data file, never the
seeders. Skill slugs are frozen (renames in place via `legacy`); dropped specialities are RETIRED. "Hair & makeup"
is now Makeup (slug `makeup`) + Hair. Tests must not reference `hair-makeup`. List skills with
`TalentType::alphabetical()`. NOT yet ported: per-skill "About your practice" fields (fields.js), audio/writing-
sample sections, icons for the 32 new skills (fallback glyph).
PRACTICE FIELDS + LOCKED LINK (2026-11-17, ADR-AG/AH; suite 910 green). `App\Support\PracticeFields` is THE home
of the per-skill "About your practice" catalog (27 fields, 31 skills) — the editor (resources/js/ui/practice-fields.js,
wired in profile-structure.js via bindPractice/mountFields) gets the definitions as JSON; never redefine fields in JS.
Values: `talents.practice` JSON via `PracticeService` (`PUT /talent/profile/practice`; the controller reads the FULL
`fields` input — validated() would drop keys without rules — and `clean()` is the guard). `digitals` and `software`
are DERIVED (Digitals content / software_stack) — never store them in practice. The comp_card block is silent once
practice holds height/measure/sizes. A Private profile's link is shown LOCKED (supersedes the old "no link" rule).
PROTOTYPE GAP-FILL (2026-11-17, ADR-AI…AM; suite 927 green, build green, fresh migrate+seed exits 0):
- Hide ONE credit/recognition from your profile (`hidden_at`; `Recognition::isShown()` is the public test; a hidden
  credit stays on the project page). Skill glyphs: ONE map `resources/data/skill-icons.json` (Blade + dashboard.js).
- Projects take likes (`projects.likes_count`, same `likes` rows; `famaProjectLike` replaced Follow on the project page).
- Experience = LinkedIn-style roles on `brand_collabs` (role columns); rules in `App\Support\Experience` mirrored by
  resources/js/ui/experience.js — change both together. Roles save with the Profile structure draft
  (`PUT /talent/profile/experience`).
- Reposts: `post_type=repost` + `reposted_post_id`; `RepostPost` is the only writer; every post-card eager load must
  include `repostOf.author.media`, `repostOf.media`, `repostOf.project.media`.
- NOT built on purpose (ADR-AL): audio / writing-sample sections (dormant in the prototype). New skills = Projects-only tab.
- Free-form project blocks: BUILT (see below).
FREE-FORM PROJECT BLOCKS (2026-11-17, ADR-AN; suite 939 green, build green, fresh migrate+seed exits 0). The story
is `projects.blocks` — `App\Support\ProjectBlocks` is THE home (shapes, limits, Form Request rules via
`ProjectBlocks::rules()`, legacy conversion, summary/body projections); `App\Support\ProjectStory` is THE page
layout (port of project.html render) drawn by `public/partials/project-story`. Editor = `resources/js/ui/project-editor.js`
(`famaProjectEdit`, moved out of projects.js; shared helpers in `ui/project-util.js`) + `talent/partials/project-edit-story`.
WATCH OUTS: (1) pictures are media IDS of the project — never URLs; normalize() 422s a foreign id. (2) Never write
summary/body by hand when blocks are sent — the service derives them. (3) `orderedMedia()` hides pictures marked
`detached_at` (taken out of the story; deleted after 24 h by the next save); the editor resolves undo through
`allMedia()`. (4) The editor uploads with `place=0`; any other uploader's pictures join the last Photos block.
(5) prototype CSS needs blocks as DIRECT children of `.pe-story`/`.pe-b` — x-if templates sit straight under `.pe-b`,
the "+" inserter lives inside each block (`.pe-ins-above`). (6) Fresh seeds/factories leave `blocks` NULL →
`Project::storyBlocks()` reads the legacy fields. Owner decisions 2026-11-17: ADR-F2 confirmed; verification never
auto-publishes; Delete/Leave stay in the editor's Settings; Discovery isListed() waits for features.discover;
Arabic copy paused.
SEO (2026-11-19, ADR-AO; suite 949 green). `config/seo.php` + `App\Support\Seo` (THE per-page metadata: pass
`:seo` to `<x-fama.layout>`) + `RobotsTag` middleware + `SeoController` → `SitemapService` / `LlmsTxtService`
(/robots.txt, /sitemap.xml, /sitemaps/*.xml, /llms.txt — root routes before the locale group; public/robots.txt was
DELETED, the route serves it). WATCH OUTS: (1) indexable ROUTES are an ALLOWLIST in config/seo.php — a new public
page must be added there or it stays noindex. (2) `SEO_INDEXABLE` defaults on only for APP_ENV=production. (3) A
private profile / ?preview / ?visitor is noindex; never put a confidential client in metadata (the project page
already guards it). (4) Tests that count text on a page must count in the <body> — the <head> repeats descriptions.
DEPLOY: `config/scribe.php` returns [] when Scribe is absent, so `composer install --no-dev` works.
PROJECTS IN EVERY TAB + NO EMPTY SECTIONS (2026-11-23, suite 953 green): every skill tab has a PINNED
Projects section — `ProfileBlockService::isPinned()` (skill-scoped `projects` block) is THE rule: it can't
be hidden or removed (422) (SUPERSEDED in part by ADR-AP below: no talent-side reorder/move at all, and the
editor's Sections panel is gone). `SeedBlocksForSkill` always seeds
it, and every catalog skill lists it. Migrations 2026_11_23_003520 (backfill, appended last) +
2026_11_23_003522 (unhide). `App\Support\ProfileSections::forScope()` is THE public section list (profile
page + lazy tab endpoint) and adds a Projects section when a scope has work but no block — projects always
show. `<x-profile.block>` hides an empty section for EVERYONE (no owner "Nothing here yet"; `isOwner` prop
inert). `ProjectCreditService::ensureProjectsBlock()` stays as a safety net.
MIGRATIONS: always create with `php artisan make:migration` (user rule, 2026-11-23) — never hand-write the file.
UPLOAD MODAL (2026-11-23): every media upload runs under ONE modal — `window.famaUploading({count, kind:
'media'|'cover'|'avatar'}, onProgress => upload(url, formData, { onProgress }))` (store `famaUpload` in
ui/shell.js, component `<x-fama.upload-progress>` in the fama layout). `upload()` in http.js is the XHR twin of
`post()` (same headers/envelope/ApiError) — use it for any new file upload, never a bare `post()`, and never
build a per-page spinner. Light buttons on dark-hover pages need the full `.btn:not(.btn-2):not(.btn-text):not(
.btn-danger):not(:disabled):hover` chain to beat app-bridge's dark hover.
GSAP WATCH OUT (2026-11-23): never `clearProps: 'all'` in public/fama-ui/js/*-motion.js — it deletes the element's
entire inline style, and server-rendered inline styles (the profile avatar's background-image photo) vanish. Clear
only the props the tween set (`'transform,opacity,visibility'` for y/autoAlpha). The public profile has NO stats row.
EDIT PROFILE HAS NO FLOATING TRACKER (2026-11-23): `famaGate` (ui/profile-gate.js) no longer creates the `.fx-notice.gt`
bar under the nav — the #gate card is the only steps UI (hidden once every step is done). The tracker code paths
are inert behind `bar === null`; the nav's Private notice still stays off Edit profile.
SECTIONS PANEL REMOVED (2026-11-23, ADR-AP; suite 950 green, build green): Edit profile has NO Sections card —
talents can't reorder blocks, move them between tabs, or hide skill-tab sections; blocks keep their seeded scope
+ order. `PATCH /talent/profile/blocks/reorder` + `…/{block}/move`, `MoveBlockRequest` and
`ProfileBlockService::reorder/moveBlock` are DELETED. `…/{block}/visibility` STAYS (Explore / Experience "Show on
profile" switches + the Credits Recognitions switch). In profile-structure*.js only `blockName`/`uniBlock`/
`toggleVisible` + a switches-only `saveBlocks()` remain; the editor no longer receives `catalog`/`contentKeys`.
PROFILE STATES DRAFT → PRIVATE → LIVE (2026-11-23, ADR-AQ; SUPERSEDES ADR-AC's auto-Live; suite 957 green):
saving the Go Live steps makes a Draft PRIVATE (`unpublished`) by itself (`SyncGoLiveState`), NOT Live; the
talent presses Go Live and can Make private again (`PATCH /talent/profile/publish`, both ways; a Draft → 422).
Breaking a step on Private/Live → Draft. `ProfileCompletionService::stage()` is THE status→draft/private/live
map (`state()` returns `stage`; the editor reads `path.stage`). The "you're Live" mail fires on the real Go
Live. Nav label for talent.profile.edit is now **Portfolio** (2026-11-25; account menu + owner strip keep "Edit profile").
SIDE BAR ORDER (owner 2026-11-25): Profile → Portfolio · Projects · View profile; Community → Feed · Activity ·
Credits · Notifications. Top bar ≤1100px: Portfolio · Projects · Feed · Activity · Credits.
GUIDED ONBOARDING (2026-11-25, ADR-AR; suite 968 green, build green): `/talent/welcome/{step?}`
(`talent.onboarding`) = five screens You · What you do · Your story · Your work · Go Live, ordered for a COMPLETE
portfolio (projects get their own screen, goal of three) + a live "How brands see you" preview. It owns NO writes
— every screen saves through the existing Profile/Skills/Projects endpoints and re-reads /talent/profile/state.
THE grouping is `ProfileCompletionService::ONBOARDING` (+ `ONBOARDING_ESSENTIAL` = name·place·skill·show,
`onboardingStep()`, `needsOnboarding()`; `state()` returns `onboarding_step`). Landing: registration → onboarding;
login/`/` → Projects when done, onboarding while an essential step is missing, else Profile. Profile's steps card
is now ONE slim bar (profile-gate.js) whose action opens the onboarding screen holding the next step. Project editor
`?from=welcome` → back link to the Work screen. Skills editor payload lives in `SkillsService::editorPayload()`. The onboarding preview FRAMES the real profile: `/{slug}?embed=1`
(owner only → bare layout, no strip/footer/motion) scaled from 1280px; the draft is posted in via
`public/fama-ui/js/profile-embed.js` into `data-pv` hooks (name/headline/place/bio) — keep those hooks on the
profile header if you restyle it. Profile editor PATCHes only CHANGED core keys (`changedCore()`).

