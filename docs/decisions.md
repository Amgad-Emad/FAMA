# Decision log (ADRs)

> Lightweight architecture decision records. Every session inherits these. Keep entries short:
> **Context / Decision / Status / Consequences**. When a decision changes the data model, update
> `docs/specs/` too. `Accepted` = build to it; `OPEN` = do not assume — needs an owner's call.

## Accepted

### ADR-1 — Multi-guard web auth
- **Context:** Three distinct login entities (admin, brand, talent) plus a future mobile app.
- **Decision:** Breeze multi-guard session auth — `admin → users`, `brand → brands`, `talent → talents`. Sanctum for the mobile API.
- **Status:** Accepted.
- **Consequences:** Separate guarded route groups/dashboards per entity; a single role-aware login dispatches to the right guard (see ADR-D). Phase 0 default guard is `admin` (only migrated auth table); brand/talent tables land in Phase 1. Sanctum tokens issued in Phase 4.

### ADR-2 — Services + Actions, thin controllers, one envelope
- **Context:** Web-Ajax and API must never drift; logic must be reusable and testable.
- **Decision:** Business logic lives in Services and single-purpose invokable Action classes; controllers are thin and only orchestrate; every action returns a `Response`. Web-Ajax and API share one JSON envelope and the **same** services.
- **Status:** Accepted.
- **Consequences:** `{ success, data, message, errors, meta }` via `ApiResponse` + `response()` macros; DTOs flow Form Request → Service → Resource; controllers hold no queries/logic.

### ADR-3 — State machines for every lifecycle
- **Context:** Many entities have documented, guarded lifecycles.
- **Decision:** `spatie/laravel-model-states` models every documented lifecycle: contract, contract_step, contract_message, project, brand, talent profile, review, media.
- **Status:** Accepted.
- **Consequences:** Transitions are explicit, guarded, and auditable; each stateful model gets a state column + State classes (built with the model in Phase 1+). *(The availability, service, and affiliation lifecycles were removed with their features — see ADR-K/L/M.)*

### ADR-4 — Configurable contract-flow steps (Strategy/Factory) + snapshot
- **Context:** Admins configure contract flows; in-flight contracts must stay stable when a flow is edited.
- **Decision:** One `StepHandler` per `step_type` (form/approval/upload/payment/contract/message/schedule/info) via Strategy + Factory. Flow steps are **snapshotted** into `contract_steps` at contract creation.
- **Status:** Accepted.
- **Consequences:** Editing/archiving a flow affects only **future** contracts; adding a step type = a new handler, no schema change; final-payment automation is a handler setting (see ADR-B).

### ADR-5 — Media library is the source of truth for uploads
- **Context:** Uploaded assets vs. external links/embeds.
- **Decision:** `spatie/laravel-medialibrary` (collections + conversions for thumbnails) owns all **uploaded** files. Manual `*_url` columns for uploads are dropped and replaced by accessors resolving from the library. Plain URL columns are kept only for **external** links/embeds (YouTube/Vimeo, social, brand-collab, press).
- **Status:** Accepted.
- **Consequences:** Thumbnails are conversions, not columns; `docs/specs/schema-master.md` documents the original URL columns with an implementation note.

### ADR-6 — Promote query-critical JSON to pivots / indexed columns
- **Context:** Discovery & search must filter efficiently; several spec fields are JSON arrays.
- **Decision:** Promote query-critical arrays to pivot tables / indexed columns — `brand_creative_needs.talent_types`, `brand_aesthetics.mood_tags`, `equipment`, `software_stack`. Non-queried preferences stay JSON.
- **Status:** Accepted.
- **Consequences:** Indexed filtering with `spatie/laravel-query-builder`; a little denormalization; exact pivots defined in Phase 1. (Supersedes the earlier "discovery/search schema additions" open item.)

### ADR-7 — i18n + RTL
- **Context:** Bilingual product (English/Arabic), Egypt-first, RTL required.
- **Decision:** `mcamara/laravel-localization` for locale-prefixed routes (`/en`, `/ar`) + `spatie/laravel-translatable` for translatable attributes; RTL UI.
- **Status:** Accepted.
- **Consequences:** Default locale hidden in URL (`/login` and `/ar/login` both work); language switcher; `dir`-aware layouts with logical utilities; the translatable-attribute policy lives in `docs/conventions.md`.

### ADR-8 — Quality gates
- **Context:** Reliability of a multi-write, money-touching domain.
- **Decision:** Pest unit + feature tests; dedicated fail-log channels; `DB::transaction` on every multi-write operation.
- **Status:** Accepted.
- **Consequences:** Log channels `app`/`auth`/`contracts`/`media`; `Service::runInTransaction()` wraps transaction + failure logging (catch → log to channel with context → rethrow / error envelope); green tests are part of the Definition of Done.

### ADR-9 — Docs & process discipline
- **Context:** Keep project knowledge coherent and avoid sprawl.
- **Decision:** Docs are a fixed set, updated **in place**; a descriptive docblock sits above every function; **no git operations, ever**.
- **Status:** Accepted.
- **Consequences:** `docs/` updated in place each phase + a dated `changelog` line; agents never `add`/`commit`/`push`.

### ADR-10 — Tailwind v4 toolchain *(retained from Phase 0)*
- **Context:** Breeze's installer scaffolded a Tailwind **v3** setup into a project whose Vite plugin is v4.
- **Decision:** Standardize on Tailwind **v4** via `@tailwindcss/vite`; dark mode is the **class** strategy.
- **Status:** Accepted.
- **Consequences:** v3 config files removed; `app.css` uses `@import 'tailwindcss'` + `@plugin '@tailwindcss/forms'` + `@custom-variant dark` + `@source`/`@theme`; `@tailwindcss/vite` restored in `vite.config.js`.

### ADR-K — Rate card / services removed
- **Context:** The per-talent rate card (`services` table + `Service` state machine) duplicated pricing that is better expressed once, and it coupled the contract engine to a talent-owned catalog.
- **Decision:** Remove services entirely (table, model, factory, state machine, policy, routes/controllers/requests/resources, the `services` profile block, and its key from every `default_blocks` seed + existing `profile_blocks`). The single Pricing rate (`talents.booking_type`/`booking_value`) replaces it. **Contract amount comes from the flow, not a service** — captured by the flow's form/quote step (`FormStepHandler` amount field). `service_id` is dropped from `contracts` and `contract_enquiries` (FK + column).
- **Status:** Accepted.
- **Consequences:** Contracts no longer reference a service; `ContractResource`/contract payloads, `InitiateContract`, `ConvertEnquiryToContract`, `StoreEnquiryRequest`, and the enquiry Blade lose all service references. Append-only drop migrations (`2026_07_10_000100`, `_000300`, `_000400`) remove the schema and clean already-migrated databases.

### ADR-L — Availability & travel removed
- **Context:** The availability badge/state machine and the travel/rate-tier fields added lifecycle and UI surface that the product no longer wants; enquiries were gated by availability.
- **Decision:** Remove `availability_status` (+ its discovery index and `Availability` state machine), the public-profile availability badge, the enquiry availability gate, and `willing_to_travel` / `travel_regions` / `rate_tier`. **Enquiries are now always allowed.** `rate_tier` is superseded by the single Pricing rate (ADR-K).
- **Status:** Accepted.
- **Consequences:** The availability & travel dashboard route/controller/request/resource, sidebar entry, Alpine handler, discovery filter, and dashboard-home badge are gone. Drop migration `2026_07_10_000200` removes the columns (index first).

### ADR-M — Affiliations & press removed
- **Context:** Agency affiliations and press features were low-signal profile satellites carrying their own tables, models, and (for affiliations) a state machine.
- **Decision:** Remove `agency_affiliations` and `press_features` (tables, models, factories, the `Affiliation` state machine, `AgencyAffiliationPolicy` + `PressFeaturePolicy`, routes/controllers/requests/resources), the `press` profile block (catalog row + content editor + public partial + its key from `default_blocks`), and the affiliations section/partial.
- **Status:** Accepted.
- **Consequences:** Drop migrations `2026_07_10_000300` (tables) and `_000400` (block types + existing `profile_blocks` rows) remove the schema.

### ADR-N — "Skills" rename, editor consolidation & Pricing rate
- **Context:** The talent dashboard had five profile-ish tabs (Profile editor, Professions, Account) plus the "Professions" label, which the product wants to simplify to a single Profile surface and rebrand as **Skills**. The removed rate card (ADR-K) also left the profile with no way to state a price.
- **Decision:**
  1. **Terminology:** "Professions" is renamed **Skills** across all user-facing copy, Blade, translation keys, and routes (route segment `professions` → `skills`; names `talent.professions*` → `talent.profile.skills*`). Talent-side symbols follow: `ProfessionsService` → `SkillsService`, `ProfessionController` → `SkillController`, `StoreProfessionRequest` → `StoreSkillRequest`.
  2. **Persistence unchanged:** the `talent_types` table, `TalentType` model, and `talent_type_id` FKs stay as the physical layer — **`talent_types` is the Skills catalog**. A physical table rename would cascade into brand creative-needs, projects, and API lookups, so it is a deliberate **future** migration, not done here.
  3. **Editor consolidation:** the standalone **Professions** and **Account** tabs are folded into the **Profile editor**. The sidebar becomes **Home · Profile · Content · Reviews · Contracts**. The Profile editor holds Identity, Skills, **Username** (the public `slug`, relabelled "Username" in UI + validation — column unchanged, still unique/auto-generated), Publish (`is_published`, moved from Account), Pricing rate, and Blocks. Publish moves to `PATCH /talent/profile/publish`.
  4. **Pricing rate:** new nullable `talents` columns `rate_unit` ENUM(project, day, hour) / `rate_amount` DECIMAL(10,2) / `rate_currency` CHAR(3), edited in the Profile editor. All-or-nothing (a complete rate or none), NOT translatable. Replaces the removed rate card (ADR-K); public display is a later prompt.
- **Status:** Accepted.
- **Consequences:** Migration `2026_07_10_000500` adds the rate columns. The admin "profession template manager" relabel + `ProfessionCatalogService` → `SkillCatalogService` rename apply **when the admin side is built** (not present in the current talent-slice codebase). No `api-docs` regeneration was possible — the mobile API is Phase 4 and there is no `composer api-docs` script yet; `docs/api.md` (the contract doc) is updated by hand instead.

### ADR-O — Instagram-style profile header (cover/hero removed)
- **Context:** The public profile led with a large cover/hero banner. The product wants an Instagram-style, avatar-led header that reads as a personal profile and surfaces the new Pricing rate.
- **Decision:**
  1. **Remove the cover/hero image.** The `hero` media collection + `hero_image_url` accessor on `Talent` are removed, along with the hero uploader in the Profile editor (`uploadHero` action, `profile.hero` route, `TalentProfileService::setHeroImage`, the Alpine hero state). No column drop is needed (hero was a media-library accessor). The **`avatar`** collection stays. *(The `hero` **block type** in the catalog/`default_blocks` is a separate concept and is untouched — it is still skipped in the render loop because the header replaces it.)*
  2. **Instagram-style header** (token-only, dark + light + RTL): a large circular **avatar** (initials/gradient fallback), **display_name** + **@username** (the `slug`) + an optional "…" menu (copy profile link), a secondary line (primary **Skill** or headline), a three-item **stats row** — **Projects** (count of `projects`) · **Views** (`view_count`) · **Rating** (avg of approved reviews, hidden when none), the **bio**, an optional external-link row, and **Contact** / **Leave a review** CTAs. A thin token-based accent strip tops the page.
  3. **Pricing rate shown** near the identity as a "From {currency} {amount} / {unit}" chip (ADR-N), hidden entirely when no rate is set.
- **Status:** Accepted.
- **Consequences:** Header stats read from already eager-loaded relations (`projects`, approved `reviews`) — no N+1. Demo/showcase seeders no longer attach a hero cover image. Everything is built from existing design tokens (`resources/css/app.css`), verified in dark, light, and RTL.

### ADR-P — Public profile primary CTA is "Message" (interim = brand-auth redirect)
- **Context:** The public profile's primary CTA was **Contact** (the no-login enquiry/booking flow, Prompt C / ADR-O). The product wants the primary CTA to be **Message** — the entry point to the brand↔talent chat — but the chat isn't built yet. The chat is also the **contract-initiation** entry point (a brand messaging a talent starts the conversation that becomes a contract), which ties to the still-open contract-initiation slice.
- **Decision:** The public profile's primary CTA becomes **Message** (superseding Contact; "Leave a review" stays as the secondary CTA). It points at a reserved, talent-scoped named route **`brand.talents.message`** (`GET /brand/talents/{talent:slug}/message`, `App\Http\Controllers\Brand\TalentMessageController`). The route is public and branches on brand auth itself:
  - **Not authenticated as a brand** → store this talent's public profile as the intended return URL (`url.intended`) and redirect to brand authentication (`route('login', ['role' => 'brand'])`; the login view pre-selects the Brand role from `?role`), so a later iteration can open the chat straight after auth.
  - **Authenticated as a brand** → interim stub: redirect back to the profile with a **"Messaging coming soon"** flash. A clearly-marked `TODO(brand-messaging)` hook marks where the real brand↔talent chat (`contract_messages`) / contract initiation attaches.
- **Status:** Accepted (interim). No new tables, no chat wiring yet.
- **Consequences:** The `talent.enquire` route/controller (contract_enquiries) is untouched — only the CTA changed. When the brand↔talent chat / contract-initiation slice lands, it replaces the stub body in `TalentMessageController` (and the brand contract room, Phase 2C, hangs off the same thread). **This is the concrete home for the open contract-initiation decision** — see the contract-initiation notes in `talent-spec.md`.

### ADR-Q — Profile blocks & projects are skill-scoped (`talent_type_id`)
- **Context:** A talent can have several skills. The product wants the public profile to render **one tab per skill**, so blocks (and projects) must belong to a skill rather than sitting in one flat list.
- **Decision:**
  1. **Scope column.** `profile_blocks` and `projects` gain a nullable `talent_type_id` FK (nullOnDelete). **NULL = a profile-level / universal item rendered above the tabs; NOT NULL = the item lives in that skill's tab.** `profile_blocks.position` is now ordered **within a scope** (per `talent_id, talent_type_id`), indexed `(talent_id, talent_type_id, position)`. A backfill stamps existing projects to the talent's primary skill and existing blocks to the single skill their gate matches (universal/ambiguous → NULL).
  2. **Per-skill seeding (behavioural change).** Adding a skill seeds **that skill's** `default_blocks` into its own tab (`SeedBlocksForSkill`, replacing the old global-merge `SeedProfileBlocks`/`MergeDefaultBlocksForTypes`). Dedupe is scoped to **(talent, skill, block_type)**, not global — so a model-photographer gets its own **gallery in both tabs** (each with its own `portfolio_items` via `block_id`). *(The demo/showcase seeders curate a cleaner layout: universal talent-level blocks — hero, reviews, brand collabs — sit once at profile level, while gated + repeatable blocks go per-tab.)*
  3. **`is_repeatable` reinterpreted** as "may appear more than once **within one scope**". The block picker is per-scope: it offers only blocks eligible **in the scope being added to** (a `by_type`/`by_category` block only in a tab of a skill it's gated to; universal blocks in any tab **or** the universal section) and omits non-repeatable blocks already present **in that scope**. Blocks can **move between scopes** (`moveBlock` re-stamps `talent_type_id`, validated for eligibility + per-scope repeatability).
  4. **Removing a skill deletes its tab's blocks but PRESERVES content:** gallery items are un-linked (`portfolio_items.block_id` → NULL) and the skill's projects are un-scoped (`projects.talent_type_id` → NULL). The UI requires an explicit confirmation; the removal is logged.
- **Status:** Accepted.
- **Consequences:** New endpoints/params — block add/reorder are scoped (`talent_type_id`), plus `PATCH /talent/profile/blocks/{block}/move`; the projects editor gains a skill selector (defaults to the primary skill). The public profile renders a universal section + a tab per skill (all block content stays eager-loaded — no N+1). Migration `2026_07_11_000200` adds the columns/indexes and reports backfill counts.

### ADR-R — Public profile = two regions (identity/universal, then lazy skill tabs)
- **Context:** With skill-scoped blocks (ADR-Q), the public profile should read as **identity + universal data first, then one tab per skill** — shareable, back-button-friendly, and cheap to load.
- **Decision:**
  1. **Region 1 (always visible):** the Instagram-style header (avatar, name, @username, stats, Message + Leave-a-review CTAs — no cover), the universal/meta data (location, **Pricing rate**, skill chips — a chip activates its skill's tab), and the **profile-level blocks** (`talent_type_id = NULL`, visible, in position order).
  2. **Region 2 (skill tabs):** one tab per linked skill **that has visible blocks** (ordered primary-first, primary active by default); a tab whose skill has no visible blocks is hidden; a single-skill talent shows **no tab bar** (its blocks render directly). A tab's panel = that skill's visible blocks (each via its block-type partial, grandfathered types still render); the **Projects** block lists only that skill's projects.
  3. **Lazy + deep-linked:** the active tab renders **server-side**; other tabs are fetched on first click via `GET /{slug}/tab/{skill}` (envelope `{ html }`, eager-loaded, no N+1) and cached client-side. The active tab is mirrored in the URL (`?skill={slug}`) so it's shareable and the back button works (`popstate`). `view_count` bumps once per profile view, not per tab switch.
- **Status:** Accepted.
- **Consequences:** New route `talent.tab` + the `profileTabs` Alpine component. `App\Http\Resources\PublicProfileResource` documents the shape the mobile API (Phase 4) returns: `identity` (header + universal/meta + pricing rate + skills), `universal_blocks`, and `skills[]` each with `blocks[]`. No `composer api-docs` regeneration — the mobile API isn't built yet; `docs/api.md` is the hand-maintained contract.

### ADR-S — Skills are named as disciplines, not people
- **Context:** The six `talent_types` (the Skills catalog — ADR-N) were seeded as person-nouns (Model, Photographer, Cinematographer (DOP), Creative Director, Stylist, Graphic Designer). A skill is an *activity a booking is for*, so it should read as the discipline — "Modeling", not "Model".
- **Decision:**
  1. **Rename the six rows to disciplines:** Modeling, Photography, Cinematography, Creative Direction, Styling, Graphic Design, with matching slugs (`modeling` / `photography` / `cinematography` / `creative-direction` / `styling` / `graphic-design`), icons (`lucide-<slug>`), and en+ar `name`/`description`. A data migration (`2026_07_11_000300`) renames existing rows and the `TalentTypeSeeder` produces the new values, so `migrate:fresh --seed` and an in-place migration converge. **`talent_types` IDs are unchanged**, so every FK (`talent_talent_type`, `block_type_talent_type`, `brand_project_talent_types`, `brand_creative_need_talent_type`, `profile_blocks.talent_type_id`, `projects.talent_type_id`) is untouched.
  2. **`category` stays the enum** `model | crew | creative` (it gates blocks and scopes discovery filters); only its **display labels** become Modeling / Crew / Creative. When a category group holds a single chip whose label would duplicate the group header (Modeling), the **redundant header is suppressed**.
  3. **No redirects:** old `?skill=<old-slug>` deep links break — accepted pre-launch.
- **Status:** Accepted. *(The Arabic names are a first pass — stakeholders should confirm the AR copy; `عرض الأزياء` / `التصوير الفوتوغرافي` / `التصوير السينمائي` / `الإدارة الإبداعية` / `تنسيق الأزياء` / `التصميم الجرافيكي`.)*
- **Consequences:** Data-driven surfaces (talent-card kicker, public-profile secondary line + skill chips, admin Skills catalog, brand creative-needs, API lookup) show the new names automatically. Display-label code changed in `discover.blade.php` + `dashboard.js` (`scopeLabels`) and the editor's per-skill category label. Every seeder/factory/test that referenced an old slug or name literal was updated; nothing references `photographer` / `stylist` / etc. as a slug literal.

## Open — needs owner (surface every session)

### ADR-A — Three brand-side user modes
- **Context:** Brand accounts appear to support multiple user modes with different access.
- **Decision:** TBD — define the modes and their access/authorization logic.
- **Status:** OPEN — needs owner (**product**).
- **Consequences:** Shapes the brand guard/authorization model and possibly a `brand_users` (roles) table; blocks brand team/permission features.

### ADR-B — Final-payment-leg automation boundary
- **Context:** Whether the last payment step auto-completes or requires a manual confirmation.
- **Decision:** TBD — manual confirm vs. auto, expressed as a `PaymentStepHandler` setting.
- **Status:** OPEN — needs owner (**Kanta / billing**).
- **Consequences:** Defines `PaymentStepHandler` config and what triggers contract completion (ADR-4).

### ADR-C — Talent admission flow
- **Context:** Open self-signup vs. an admin-approval gate for talents.
- **Decision:** TBD — choose the admission model.
- **Status:** OPEN — needs owner (**product**).
- **Consequences:** Drives onboarding routing and the default of `talents.is_active` / publish gating.

### ADR-D — Web login UX
- **Context:** One unified login vs. separate per-role login routes.
- **Decision (updated 2026-07-18):** **Hybrid.** The public login stays unified and role-aware for the
  two marketplace entities (`/login`, segmented Talent | Brand control, `LoginRequest` accepts only
  those roles, absent role defaults to talent; `?role=brand` deep link per ADR-P). **Staff sign in on a
  fully separate screen** — `GET|POST /admin/login` (`admin.login[.store]`), its own enterprise-styled
  view (`admin/auth/login`), and `AdminLoginRequest` (subclasses `LoginRequest` to inherit rate limiting
  + single-active-identity, guard pinned to `admin`, no role field). Guests are redirected per area
  (`redirectGuestsTo`: first path segment — or second after a 2-letter locale — `admin` → staff login).
- **Status:** Accepted for admin-vs-public. Talent-vs-brand remains unified (revisit only if the
  marketplace flows diverge).
- **Consequences:** The public form can never authenticate the admin guard (`role=admin` fails
  validation). Breeze's User-based auth tests target `/admin/login`. The staff view is `noindex`.

### ADR-E — Brand-spec completeness
- **Context:** Some brand-side spec sections originally referenced unfetchable Claude artifact URLs and may have gaps.
- **Decision:** Confirm completeness against `docs/specs/brand-spec.md` before building Phase 2.
- **Status:** **Resolved (Accepted)** — confirmed at the start of Phase 2A. `brand-spec.md` is a complete
  transcription (all pages, workflows, lifecycles) and covers every schema-master §4/§5 table; no gaps.
- **Consequences:** Phase 2 (brand) build unblocked; Phase 2A schema shipped against the confirmed spec.

### ADR-F — `contracts.brand_project_id` FK *(retained from Phase 0)*
- **Context:** Brand/project workflows reference a contract running under a project, but the FK isn't in the `contracts` definition.
- **Decision:** Add `brand_project_id → projects (nullable, nullOnDelete)` on `contracts`.
- **Status:** **Resolved (Accepted)** — added in Phase 2B (`add_brand_state_and_campaign_link`) once
  `brand_projects` existed. `Contract::project()` / `Project::contracts()` relations wired.
- **Consequences:** A project groups many contracts; a completed public project is a profile showcase.

### ADR-G — Brand/talent password-reset tables *(retained from Phase 0)*
- **Context:** Phase 0 points the `brands`/`talents` password brokers at the shared `password_reset_tokens` table (inert — unused yet).
- **Decision:** TBD — dedicated per-entity reset tables vs. shared, decided when those auth flows are built.
- **Status:** OPEN.
- **Consequences:** Affects Phase 1 auth flow migrations for brand/talent.

### ADR-H — Admin RBAC: role enum vs. spatie/laravel-permission
- **Context:** schema-master §6 lists `users.role` as "ENUM(talent, brand, admin) — or use a roles
  table". Admin governance (flow authoring, moderation, contract-step intervention, settings, staff
  management) needs *granular, per-capability* authorization, not a single coarse label.
- **Decision:** **Accepted — spatie/laravel-permission**, bound to the **`admin`** guard. No `role`
  enum column on `users`. Roles (super-admin / moderator / support) compose granular permissions
  (manage-flows, moderate-content, intervene-contracts, manage-settings, manage-users); `User` uses
  `HasRoles` with `$guard_name = 'admin'`.
- **Status:** **Resolved (Accepted)** — Phase 3A. Package installed, tables migrated,
  `RolesAndPermissionsSeeder` seeds the roles/permissions and grants the demo admin super-admin.
- **Consequences:** Admin screens gate on permissions (e.g. `@can('manage-flows')`), so new capabilities
  are added as permissions without schema changes. Talent/brand entities keep their own guards and are
  unaffected (their "role" is their guard/table, not a spatie role).

### ADR-T — Block governance split: catalog owns eligibility, skills own preselection
- **Context:** The admin Skills page edited each skill's `default_blocks` against the FULL `block_types`
  list, which quietly made it an eligibility surface: preselecting a block a skill wasn't gated for
  implied the gate didn't matter. Eligibility (`availability` + `block_type_category` /
  `block_type_talent_type`) and preselection (`talent_types.default_blocks`) are different decisions with
  different owners.
- **Decision:** Two admin pages with a hard boundary.
  1. **Block Catalog Manager** (`/admin/blocks`, new permission **`manage-blocks`**, super-admin only by
     default) owns which block types EXIST and WHO can use them: create/edit (translatable name +
     description, icon), `availability` universal / by_category / by_type with the pivot gates synced to
     the active mode (stale gates removed), `is_active` (platform on/off), `is_repeatable` (once per
     scope, ADR-Q), `default_layout`, `content_source`, and `settings_schema` (validated well-formed
     JSON). **Guard rails:** `key` and `content_source` are immutable once any `profile_blocks` reference
     the type (422); deactivating or narrowing eligibility **grandfathers** existing placements — they
     keep rendering, the type just stops being offered (the talent picker already filters `is_active` +
     eligibility). Deletion is not offered.
  2. **Skills Template Manager** (`/admin/skills`, unchanged permission `manage-flows`) owns ONLY the
     ordered preselection per skill: it offers exactly the blocks the catalog makes eligible for that
     skill (`ProfileBlockService::isEligibleForScope` — the same predicate the talent picker uses), drag
     to order, and flags a preselected key that has since become ineligible ("no longer eligible",
     removable). Server-side, `SkillCatalogService::updateDefaultBlocks` rejects ADDING an ineligible
     key but allows reordering/removing around a stale one (forcing cleanup would block unrelated edits).
- **Status:** Accepted (2026-07-16). Both managers are two-layer gated (`can:` middleware + service
  re-check) and every mutation is activity-logged (`catalog` log).
- **Consequences:** `default_blocks` can hold grandfathered keys; consumers (SeedBlocksForSkill) already
  treat it as a plain key list. The `manage-blocks` permission is seeded and granted to super-admin only —
  grant it to other roles per need.
