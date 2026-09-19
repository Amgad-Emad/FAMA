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
- **Follow-up (2026-08-12) — the `hero` BLOCK TYPE is retired too.** Keeping it "untouched but skipped" left talents an editable block row (title + Visible toggle) that changed nothing on the public profile. Migration `2026_08_12_000100_retire_hero_block_type` deletes every `hero` placement, strips the key from each skill's `default_blocks`, and flips the catalog entry to `is_active = false`; `TalentTypeSeeder` no longer lists it and `BlockTypeSeeder` seeds it inactive. The `block_types` row itself stays (keys are frozen, deactivated types are grandfathered), and the defensive `key !== 'hero'` filters in `profile.blade.php` / `PublicProfileResource` / `TalentProfileController` stay as belt-and-braces for any un-migrated data.

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

### ADR-F1 — Polymorphic post author, posting gated to talents by policy
- **Context:** The feed launches with talents as the only publishers, but the product may later let brands post. A `talent_id` FK would make that a migration + backfill.
- **Decision:** `posts.author` is a **polymorphic morph** (aliased `talent`; `brand` reserved), and every actor-side feed reference (liker, follower, comment author, reporter, viewer) is likewise a morph. Posting is restricted to talents by **`PostPolicy::create` alone**.
- **Status:** Accepted (2026-07-22).
- **Consequences:** Enabling brand posting is a one-line policy change, zero schema work. The feed enforces an aliased morph map (`talent`, `brand`, `user`, `post`, `comment`, `project`); implementation must fold the existing contract-engine morph keys into the same map so enforcement doesn't break in-flight contract data.

### ADR-F2 — Project shares: live reference + `shared_snapshot` fallback
- **Context:** A `project_share` post references a talent's Project. Snapshot-everything (the contract-step rule, ADR-4) would show stale content; a bare FK breaks the post when the Project goes away.
- **Decision:** The post **live-references** `projects.id` and renders the current Project; a `shared_snapshot` JSON frozen at share time lets an unpublished/deleted Project degrade to a **dead-but-intact frame** (no link-through) instead of a broken post. *(Widened in revision 5W:* the snapshot captures `{title translations, cover_media_path, client_name, year, media_count, skill_ids, shared_at}` so the dead frame renders complete — cover, meta line and skill chips — marked "This Project is no longer available".)
- **Status:** Accepted (2026-07-22) — ⚠️ deliberately deviates from the contract-step snapshot rule; flagged for stakeholder confirmation (feed-spec Q1).
- **Consequences:** Shares always show current work (the feed is a living surface, unlike a legal contract trail); withdrawal of a Project withdraws its content from the feed while preserving thread integrity. `posts.project_id` is `nullOnDelete`.

### ADR-F3 — Mentions stored as entity ID + offset, never as username text
- **Context:** Usernames (the `slug`) are user-editable; storing `@username` strings in rendered bodies breaks every historical mention on rename.
- **Decision:** The raw body is stored **as typed**; each mention is a `mentions` row holding the mentioned entity's **morph + ID + `start_offset` + `length`** (multibyte-correct character offsets). The renderer resolves the **current** Username at read time. A body edit deletes and fully re-parses — no offset diffing.
- **Status:** Accepted (2026-07-22).
- **Consequences:** Username changes never break links; rendering escapes first then injects markup, with `<bdi>`/Unicode isolates for mixed-direction bodies. Limits: 10 mentions/post, 5/comment.

### ADR-F4 — Link chips parse from the URL string; enrichment is best-effort and async
- **Context:** Instagram and peers block scraping; a chip that waits on a network fetch renders late or never.
- **Decision:** A `LinkProvider` driver registry parses **provider + handle/resource from the URL string alone — zero network** — so the chip is instantly useful. A queued `FetchLinkPreviewJob` enriches (oEmbed → Open Graph) best-effort into the deduplicated `link_previews` row (`url_hash`); failure keeps the parsed chip and never surfaces an error.
- **Status:** Accepted (2026-07-22).
- **Consequences:** Adding a provider is a driver + config line. The fetcher carries a mandatory SSRF guard (scheme allowlist, DNS-resolved private/reserved-range rejection, redirect/timeout/size caps) and never renders remote HTML; `embed_html` only for allowlisted providers, sanitized. This introduces the project's first queued jobs — the queue connection and a feed fail-log channel land with it.

### ADR-F5 — Feed pagination = cached candidate-ID list keyed by actor + seed
- **Context:** Offset pagination over a ranked, moving feed drifts: new posts shift pages, producing duplicates and gaps mid-scroll.
- **Decision:** `FeedService` computes the **full candidate ID list once per feed session** (tier 1 seeded-shuffled follows + tier 2 discovery, interleaved, diversity-capped), caches it **10 minutes keyed by viewer + seed**, and slices it per page. The cursor is an opaque base64 `{seed, offset}` token. Guests/zero-follow viewers get discovery only, seeded per session (D14).
- **Status:** Accepted (2026-07-22).
- **Consequences:** Provably stable paging (tested with mid-scroll inserts); new posts appear on the next session/refresh, not mid-scroll. Hydration is one `whereIn` + `FIELD()` ordering. Ranking weights live in `config/feed.php`, never in code.

### ADR-F6 — Denormalized counters in-transaction + nightly reconciliation
- **Context:** Feed lists render counts on every row; live `COUNT(*)`s don't scale, and async counters drift visibly.
- **Decision:** `likes_count`, `comments_count`, `shares_count`, `replies_count`, `followers_count`, `following_count`, `posts_count` are **denormalized columns mutated inside the same DB transaction as the source write** (CounterService + observers). A nightly `feed:reconcile-counters` recomputes from source and reports drift.
- **Status:** Accepted (2026-07-22).
- **Consequences:** Reads are free; any bug-induced drift self-heals within a day and is logged. The reconcile command is covered by a test that corrupts counters and asserts repair.

### ADR-F7 — Two-level comment depth
- **Context:** Unlimited nesting produces unreadable threads and unbounded recursive queries.
- **Decision:** **Comment → reply, two levels, hard.** A reply to a reply attaches to the same `parent_id` and auto-prefixes an `@mention` of the reply author (the Instagram model).
- **Status:** Accepted (2026-07-22).
- **Consequences:** `comments.parent_id` is one self-FK deep; listing is two bounded queries (comments + preview replies); tombstones (D12) keep threads intact under deletion.

### ADR-F8 — Guest read unrestricted; write intent captured and replayed after auth
- **Context:** The feed is an acquisition surface — a login wall kills it; but a guest's tap on ✦ shouldn't evaporate either.
- **Decision:** Every read route allows guests. A guest write returns **401 with the JSON envelope carrying `{action, target_type, target_id}`**; the UI expands the control inline into a sign-in prompt (no blocking modal, scroll preserved), and a `PendingIntent` session service **replays the action exactly once after successful auth** and clears it.
- **Status:** Accepted (2026-07-22).
- **Consequences:** `resolve.actor` middleware (never rejects) + `require.actor` (envelope-401) on write routes; extends the ADR-P `url.intended` pattern to actions. Covered by end-to-end replay tests for like, follow and comment.

### ADR-F9 — Saved and liked collections are private
- **Context:** Bookmarks (revision 5X) add SAVED/LIKED dashboard views. Some platforms surface likes publicly as a social signal; a casting marketplace's shortlists are competitively sensitive.
- **Decision:** Saved and liked collections are **private, visible only to their owner**. No public likes tab on profiles; every collection endpoint is scoped to the current actor with no ability to request someone else's. The `bookmarks` table is polymorphic on both sides so saving **talents** (the brand shortlist) and **Projects** later is a policy flip, not a migration — only `bookmarkable_type = post` is enabled this phase (`ToggleBookmark` rejects the rest with a clear message).
- **Status:** Accepted (2026-07-22). Revisit "public likes" only as an explicit product decision.
- **Consequences:** `posts.bookmarks_count` is denormalized in-transaction and reconciled nightly (ADR-F6 pattern). Save is guest-gated with intent replay like every other write (ADR-F8).

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

### ADR-V — Contract templates: an admin-authored contract "schema" with a live document
- **Context:** The contract engine had flow-step templates but no concept of the contract's TERMS or a
  rendered contract DOCUMENT. Standard terms (payment split, cancellation forfeit, revisions, usage) had
  no home; the `contract` step type anticipated a `settings.template` reference that was never built. The
  ask was an admin surface to author the contract's terms with a live preview and apply it to contracts.
- **Decision:** A standalone **contract-template library** (`/admin/contract-templates`, gated
  `manage-flows`), linked per flow.
  1. `ContractTemplate` stores the standard-terms parameters + a **fully custom, reorderable `clauses`
     list** (`{label, body}` with numeric tokens). One default per `applies_to` scope; a default is
     protected from deactivation/deletion.
  2. A `contract_flow` optionally references a template (`contract_template_id`). At contract creation the
     resolved terms are **frozen onto `contracts.terms_snapshot`** (InitiateContract) — so editing a
     template only affects FUTURE contracts, consistent with the flow-step snapshot rule (ADR-4).
  3. **One renderer, two callers:** `App\Contracting\ContractDocument` normalizes a template (for the
     builder's live preview, driven by sample inputs) or a contract (for the room, driven by live values)
     into the `$doc` consumed by `contracts/partials/document.blade.php`. The builder's preview posts to a
     debounced endpoint that renders the SAME partial, so it is WYSIWYG-accurate.
- **Status:** Accepted (2026-08-03).
- **Consequences:** Terms are versioned by snapshot, not by reference — a contract's document is stable
  for its whole life. Templates are authored independently of flows and reused across them. Alternatives
  rejected: embedding terms in the flow's `contract` step settings (authoring buried in the flow builder,
  no standalone document surface) and a single global template (no per-category variation). Open item:
  whether verifying/confirming terms should write a signature event into the contract timeline (today the
  document is a read-only rendering; the existing `contract` step still records the signature).

### ADR-U — Admin verification is a hard prerequisite for going public
- **Context:** Self-serve talents and brands could reach the public surfaces with no admin review — a
  talent went `Live` needing only a display name; a brand went `Published` needing only completed
  onboarding. `brands.is_verified` existed but was cosmetic (a trust badge, not a publish gate); talents
  had no verification concept at all. The requirement: nothing goes public until an admin approves it.
- **Decision:** Verification (`is_verified`) is the go-live gate for **both** entities.
  - Talents gain an `is_verified` column (migration `2026_08_02_000100`); the `ToLive` guard now requires
    `display_name` **and** `is_verified`. Brands get a new guarded `ToPublished` transition
    (`Complete→Published`, `Unpublished→Published`) requiring `is_verified`.
  - The guard is the hard floor for **every** path (self-serve and admin). The self-serve publish
    endpoints add a friendly 422 pre-check; the admin moderation UI shows **Verify** for both kinds and
    hides **Publish** until verified.
  - `verify` is **one-way** (both entities). To take a live account down, use Suspend/Unpublish — not
    un-verify. New `TalentModerationService::verify` mirrors the brand method; both audit
    (`talent.verified` / `brand.verified`).
  - Already-public rows are **grandfathered** (backfill `is_verified=true` for live talents / published
    brands) so the gate only bites future transitions.
- **Status:** Accepted (2026-08-02).
- **Consequences:** A newly-registered account is `unverified` and cannot publish until a moderator acts —
  the `created/draft` (talents) and `is_verified=false` (brands) moderation queues are now a real approval
  funnel, not a passive count. Factory defaults for the published/live states are `is_verified=true` (with
  `unverified()` states for negative tests). Open item: whether verification should also **auto-publish**
  (today it only unlocks the owner's Publish toggle) — deferred to stakeholders.

### ADR-W — V1 release scoping via feature flags (talent + feed + public profile)
- **Context:** The client wants to ship to production now with ONLY the talent side, the public feed, and
  the public talent profile. Brand, contracts, and messaging are largely built but must be hidden this
  release and returned in later versions — without deleting code or forking a throwaway branch.
- **Decision:** Config-driven feature flags (`config/features.php`: `brands`, `contracts`, `messaging`),
  **default ON** so dev/staging + the full test suite run the complete app; the **production `.env`** turns
  them OFF (`FEATURE_*=false`). Enforcement is **runtime** (so it is testable via `config([...])`): a
  `feature:<name>` middleware (`App\Http\Middleware\EnsureFeature`, disabled → 404) gates route groups, a
  `@feature('brands')` Blade directive gates views, and `App\Support\Features` is the read accessor. Nav
  arrays carry a `feature` key and are filtered. Registration/login go talent-only; the feed hides the
  brand rail/follows; the public profile hides the Message CTA; the admin hides brand/contract consoles.
  `config/feed.php` `allow_following_brands` tracks `FEATURE_BRANDS`. **Do NOT** revoke `manage-flows` to
  hide contract admin — it is shared with Skills templates; gate by feature instead.
- **Kept in V1:** Reviews (no brand/contract FK) and the talent "Brands I've worked with" portfolio block.
  Admin stays fully functional (mandatory talent verification + feed moderation).
- **Status:** Accepted (2026-08-06).
- **Consequences:** Re-enabling a feature later = flip one env var (contracts/messaging imply brands). The
  contract/brand tables + demo seeders stay; a production V1 seed runs only the catalog/config seeders
  (Roles/TalentType/BlockType/Settings) — confirm `SettingsSeeder` tolerates a null `default_contract_flow_id`
  or keep `ContractFlowSeeder` (dormant). Reversibility is test-proven (`tests/Feature/FeatureFlagsTest`).

### ADR-X — A project carries one or more skills, on its own page
- **Context:** Projects were a row in the generic content-table editor (`ContentRegistry` +
  `BlockContentController`) with a single optional `talent_type_id` scope (ADR-Q). Two things were wrong.
  A project is not a flat table row — it has a cover, credits, long-form copy and metrics, and it is the
  most important thing a talent publishes; the generic form gave it a two-field treatment. And a single
  optional scope both (a) let a project exist with no discipline at all, which makes it unplaceable on the
  public profile, and (b) forced a campaign film that is cinematography *and* creative direction into one
  tab.
- **Decision:** Projects get a **dedicated page** (`/talent/projects`) that is the ONLY write path, and a
  project must be tagged with **at least one** of the talent's own skills.
  - New pivot `project_talent_type` (unique `(project_id, talent_type_id)`, `position` = tag order).
  - `projects.talent_type_id` **stays** as the HOME tab and is kept equal to tag 0, so ADR-Q's single-scope
    readers keep working untouched.
  - The tab test is `Project::belongsToScope()` / `scopeInSkillScope()`, which matches the home column **OR**
    a tag — the pivot only ever ADDS tabs, so a row written straight to the column (factory, a seeder muted
    by `WithoutModelEvents`, un-migrated data) can never drop off the public profile.
  - The rule is enforced in `TalentProjectService` (domain, 422) *and* `StoreProjectRequest` (field error), so
    it holds no matter who calls.
  - `projects` was **removed from `ContentRegistry`**: the content page shows a signpost panel instead of a
    second editor, and `/talent/content/projects` redirects to the Projects page. The now-dead `skill_scoped`
    machinery in `BlockContentController` and the generic editor partial went with it.
- **Status:** Accepted (2026-08-18).
- **Consequences:** Removing a skill still preserves content (ADR-Q) but now re-homes each affected project on
  its **next remaining tag** and only un-scopes the ones that ran out (`SkillsService::untagProjects`).
  `ProjectFactory::configure()` mirrors a factory-set `talent_type_id` into the pivot so factories and
  seeders produce the same shape the editor writes. A project tagged with two skills appears in **both**
  tabs — a deliberate widening of ADR-R's tab semantics. Aliases/keys unchanged (a pivot needs no MorphMap
  entry). Open item for stakeholders: whether an un-scoped leftover project should stay visible in the
  universal region or be hidden until it is re-tagged (today: visible).
- **V1 trims (2026-08-18):** the composer is **English-only** — the Arabic title input is commented out with
  the same note the profile editor uses for its AR headline/bio, since V1 ships without an AR/EN toggle
  (ADR-W). `form.title.ar` stays in the payload, and `TalentProjectService::dropEmptyForeignTranslations()`
  strips blank non-English locales before storage: storing `ar => ''` would make spatie return the blank
  instead of falling back, so a title would render EMPTY on /ar. Because spatie's `setTranslations()` merges
  per locale, an Arabic value stored earlier survives an edit through the English-only editor. A blank
  **English** value is deliberately left alone — that input is on screen, so clearing it must clear the
  translation. The **cover is optional** (no rule in `StoreProjectRequest`, not part of `canSave`) and now
  says so in the UI.

---

## ADR-Y — Skills nest into a controlled speciality vocabulary (nine skills, two levels)
- **Date:** 2026-08-22
- **Context:** The client's structure specs (`profile.html`, `project.html`, `spcialist.html`) define the
  portfolio as a set of skills, each carrying its own proof, and add a **second level** the schema did not
  have: a skill nests into four-to-seven *specialities*. The spec also expands the catalog from six skills
  to nine, adding **Direction**, **Hair & makeup** and **Retouching & post**.
- **Decision:**
  - `talent_types` grows to **nine** rows (migration `2026_08_22_000200`, idempotent with
    `TalentTypeSeeder`). The spec's orthography lands too — "Modeling" → **Modelling**, title-case →
    sentence-case. **Slugs are unchanged**: `modeling` stays `modeling` because the slug is the public deep
    link (`?skill=`, `filter[type]=`) and ADR-S already broke those once; an orthographic fix does not
    justify breaking them again.
  - New `specialities` table (migration `2026_08_22_000100`) owned by one skill, plus
    `speciality_talent` and `project_speciality` pivots. `slug` is unique **per skill, not globally** —
    "Fashion" legitimately lives under photography, modelling *and* styling, and that overlap is precisely
    what lets one search assemble a whole crew.
  - **Two levels is the whole tree.** There is no third level and no free-text field anywhere: a talent
    picks from the list and can never type into it. An invented entry would never match on the project
    side, and the filter would silently stop working.
  - Every speciality answers **one question — what kind of subject**. Output format (campaign, editorial,
    lookbook, e-commerce) is a property of the project, not the person, and stays out of this tree.
    Four-to-six is the target; Photography earns a seventh for genuinely different commercial work.
  - `is_regional` flags the entries added for this market specifically (**Modest fashion**, **Arabic
    lettering**). They are commercial categories with their own clients and casting logic in KSA and the
    UAE, not filters applied to a broader category.
  - **Two rules, each enforced in exactly one place.** A talent's picks must belong to a skill they hold:
    `SkillsService::setSpecialities()` (422). A project's picks must hang off one of its tagged skills:
    `TalentProjectService::assertSkillSpecialities()` (422), validated against the skill set the project
    will **end up** with, so re-tagging skills and specialities in one request stays consistent. Never
    reimplement either.
  - Governance mirrors ADR-T's block catalog: `/admin/skills` is the only way an entry is born; the slug is
    **locked** once created (renaming it would drop every talent and project already matched through it);
    and retiring is **grandfathering, never a delete** — `is_active = false` removes it from every picker
    while existing claims keep rendering and keep being found.
- **Status:** Accepted (2026-08-22).
- **Consequences:**
  - `Speciality` needs its `speciality` **MorphMap alias** (the admin catalog writes it to the activity
    log) — as does any future model touching a morph column.
  - Removing a skill now detaches that skill's speciality claims. This deliberately does **not** follow
    ADR-Q's preserve-the-content rule: a claim carries no content, and an orphaned one would keep the talent
    in a discovery filter for a skill they no longer hold.
  - Discovery gains `filter[speciality]` (matched on slug). Because slugs repeat across skills, passing
    `fashion` alone matches fashion photographers, models *and* stylists — narrowing is what combining it
    with `filter[type]` is for. Retired entries still match so nobody drops out of search when the catalog
    moves on.
  - Categories for the new skills: **Direction = `crew`** (the spec says it deliberately mirrors
    cinematography, so it takes the same category and therefore the same eligible blocks);
    **Hair & makeup = `crew`** (a HMUA books with a kit — the equipment block); **Retouching & post =
    `creative`** (the software stack is the proof).
  - Two latent test fragilities surfaced and were fixed rather than papered over: an assertion comparing a
    *sorted* actual against an *unsorted* expectation (it only passed while modeling held a lower id than
    photography), and one comparing a raw skill name against escaped HTML (`Hair & makeup` renders as
    `Hair &amp; makeup`).
- **Open for stakeholders:** the Arabic speciality names are a first pass and need a native review; the
  spec's "left out on purpose" list (production, location scouting, set design, illustration, 3D & CGI,
  sound & music, copywriting, crew roles) is deliberately **not** seeded — promote an entry when a request
  repeats three times.

---

## ADR-Z — The portfolio is six sections: Now, About, Skills, Reviews, Recognitions, Willing to explore
- **Date:** 2026-08-22
- **Context:** The client's `profile.html` structure spec defines a talent page as six named sections. Three
  already existed in some form (About ≈ the identity header, Skills ≈ ADR-Q/R + ADR-Y, Reviews ≈ the
  reviews block). Three did not: the **Now** strip, **Recognitions**, and **Willing to explore** — plus two
  new About fields (`worldview`, `social`).
- **Decision:** Map the spec onto the EXISTING block system rather than replacing it (the stakeholder call).
  Sections that describe the *page* stay in the header; sections that are *content* become blocks.
  - **NOW** — `will_travel` and `is_available` booleans + `availability_changed_at` on `talents`, rendered
    as one strip at the top of the header. `will_travel` is a **yes/no, never a list of cities**: which
    city and on whose budget is the conversation the platform wants people to start.
    `availability_changed_at` is stamped **only when availability actually flips** — an undated toggle
    looks current forever, which is worse than showing nothing.
  - **This supersedes the availability half of ADR-L, and is NOT a revert.** ADR-L removed an
    `availability_status` ENUM (available/booked/unavailable) + `willing_to_travel` + a `travel_regions`
    JSON list. The spec asks for two plain toggles, so these are new columns with new names and simpler
    semantics. ADR-L's *other* half stands unchanged: **availability is INFORMATION, never a gate** —
    enquiries are not blocked by it, and discovery only narrows on it when a brand explicitly asks.
  - **ABOUT** — `worldview` (translatable) is taste and point of view, kept apart from `bio`
    (background and track record) and **prompted** in the editor with specific questions; an unprompted
    second textarea beside Bio just gets a second bio typed into it. `talent_social_handles` mirrors
    `brand_social_handles` so both sides of the platform describe a social presence identically.
  - **RECOGNITIONS** — peer vouches, and the distinction from reviews is entirely **who writes them**:
    a review is BRAND → talent after a booking completes (verified because the booking happened here, and
    therefore impossible at launch); a recognition is TALENT → talent, needs no booking, and so works from
    day one. The author is always a talent on the platform, which is what stops the section becoming
    anonymous praise. Both go through the **same admin moderation queue**, and `RecognitionState` is
    deliberately identical to `ReviewState`. Rendered to look **visibly different** from reviews — no star
    ratings, quote-led, relationship given prominence — because if both render as one wall of praise
    neither gets read.
  - **WILLING TO EXPLORE** — `talent_interests`: a direction tied to a skill and (optionally) a kind of
    work. **Structured, not free text**, because as prose it reads as a mood, nobody acts on it, and brands
    cannot filter it. The skill is deliberately **NOT** required to be one the talent already holds —
    naming a discipline you do not yet list is the entire point of the section.
  - **Profile-level blocks are DATA, not a hardcoded list.** `talent_types.default_blocks` seeds into one
    skill's TAB (ADR-Q), which is right for a gallery and wrong for a section describing the person — it
    would repeat down the page once per skill. New column `block_types.is_profile_default` flags the blocks
    seeded ONCE at `talent_type_id = NULL`, so the catalog governs it the way it governs eligibility
    (ADR-T's principle) instead of a list of keys living in the seeding action.
- **Status:** Accepted (2026-08-22).
- **Consequences:**
  - `SeedBlocksForSkill` now returns blocks in **two scopes** — the skill's tab blocks and, on the first
    call for a talent, the profile-level defaults. Two existing tests encoded the old invariant ("every
    seeded block is stamped with the skill", "zero universal blocks") and were updated to the new one,
    deriving the expected count from the catalog rather than hardcoding it.
  - Demo seeders build `profile_blocks` by hand (for control over positions and content), so they read
    `default_blocks` and would miss profile-level blocks entirely. Shared trait
    `Database\Seeders\Concerns\SeedsProfileDefaultBlocks` keeps that rule in one place.
  - `Recognition`, `TalentSocialHandle` and `TalentInterest` each need a **MorphMap alias** — as does any
    new model that can reach a morph column, or writes throw `ClassMorphViolationException`.
  - Discovery gains `filter[available]`, `filter[will_travel]` and `filter[open_to]` (a stated direction's
    skill slug). `open_to` is the one filter that surfaces someone who **wants** the work but has not done
    it yet — which no keyword search over prose could.
  - A peer vouches **once**; re-submitting REPLACES the earlier vouch and sends it back through moderation
    rather than throwing. An approved vouch that is then reworded must not stay public on the strength of
    the old decision.
- **Bug this surfaced (worth remembering):** the first cut of `updateNow()` compared the incoming value
  against `$talent->is_available` by hand to decide whether to stamp the date. On a model whose attribute
  was never hydrated — a fresh insert relying on the column default, a `select()`ed subset — the unset
  attribute reads as `null`, `(bool) null` is `false`, and flipping to "not available" compared
  `false !== false` and **silently saved nothing**. The fix is to assign first and let `isDirty()` (which
  consults the original attributes and treats a newly-known value as a change) decide. `TalentFactory` now
  also sets both toggles explicitly so a factory model matches a real row.
- **Open for stakeholders:** whether an approved recognition should survive a *minor* edit rather than
  always returning to the queue; whether "Willing to explore" should also accept a direction with no skill
  at all (today a skill is required, which is what keeps it filterable); AR copy for the new sections.

---

## ADR-AA — A project is client, description, media, collaborators, skills and a hidden date
- **Date:** 2026-08-22
- **Context:** The client's `project.html` structure spec. Three of its six sections already existed
  (Description, Skills & specialities via ADR-X/ADR-Y, and Client as a plain column). Three did not
  behave as specified: **Media** was a single cover rather than the ordered work, **Collaborators** did
  not exist at all, and **Date** was a `year` rendered on the page when the spec says it must be
  recorded and never shown.
- **Decision:**
  - **CLIENT is hidden when empty.** No "personal project" placeholder and no empty label. A test, a
    personal shoot, an editorial nobody commissioned: the absence says it.
  - **DATE is recorded, not displayed.** New `projects.occurred_on` (indexed with `talent_id`) so work
    can be ordered by recency and "shot recently" is answerable. It appears nowhere on the public page —
    a strong project from three years ago should not be discounted for its age, and a date in the corner
    of a card is exactly how that discounting happens. **`year` becomes a DERIVED PROJECTION** of it,
    kept because the feed's project spreads, the API resources and existing rows read it.
  - **MEDIA is the ordered work.** New multi-file `media` collection (images + video) beside the
    single-file `cover`. The ORDER is the edit: the first frame is doing the most work, so it is chosen
    rather than defaulted. Uploads append; `lead_image_url` falls back cover → first media item.
  - **COLLABORATORS: `person + skill + role → one credit`.** Skill says what someone CAN do, role says
    what they did HERE — on a shoot with six people credited, only the role separates the director from
    the assistant. **Two kinds, and the difference decides everything:**
    - **On-platform** (`talent_id`): tagging writes the credit onto THEIR portfolio, so it REQUIRES
      THEIR CONFIRMATION. Lands `pending` and is invisible *everywhere* — public project page
      included — until accepted. Without that, anyone could attach a name to work they did not do.
    - **Off-platform** (`name`): a real credit for someone not on Fama. Nobody to ask, no portfolio to
      write to, never leaves this project — so it is `listed` immediately. The confirmation rule does
      not apply because there is nothing to claim on anyone's behalf.
  - **Declining and withdrawing are the SAME transition** (`accepted → declined`). Someone who no longer
    wants to be publicly associated with a project must be able to say so at any point, not only in the
    first minute.
  - **A credited project is filed under the CREDIT's skill, not the project's** — you can be credited as
    a stylist on someone else's photography job, and it belongs under Styling on your page.
- **Status:** Accepted (2026-08-22).
- **Consequences:**
  - `Talent::portfolioProjects(?int $scope)` is THE portfolio view: owned projects in that scope plus
    accepted credits, de-duplicated. The public projects block and the profile's project count both read
    it, so the count matches what the page shows.
  - **Accepting a credit ensures a `projects` block exists in the target scope.** Discovered live:
    photography's `default_blocks` seed a gallery, not projects, so confirming a credit put the work in
    a tab that rendered nothing — the confirmation promise ("this now appears on your profile") was a
    lie. `ProjectCreditService::ensureProjectsBlock()` closes it, and any seeder writing `accepted`
    straight to the column skips that step (the showcase seeder now goes through `accept()`).
  - **A credit may name a skill the collaborator does not hold** — the project OWNER picks it. Those fall
    back to the universal region rather than vanishing into a tab that does not exist.
  - Owning a project and being named on a credit are **separate authorisation questions**, checked
    separately: owning never lets you answer on someone else's behalf, and being credited never lets you
    edit someone else's credit block.
  - Credit notifications deliberately reuse the feed notification payload shape so they land in the ONE
    notification inbox — a talent should not have to look in two places to learn they were credited.
  - `ProjectCredit` needs its `project_credit` MorphMap alias.
- **Bug this surfaced (worth remembering):** with `year` still mass-assignable, the demo seeder set
  `year` directly while the factory set `occurred_on`, and the two silently disagreed (`occurred_on`
  2022, `year` 2025). A projection that anything can write is not a projection. `year` was removed from
  `$fillable` — the mutator assigns `$this->attributes` directly and so bypasses fillable — which makes
  divergence impossible and, with `preventSilentlyDiscardingAttributes` on, turns any attempt to
  mass-assign it into a throw rather than a silent inconsistency.
- **Open for stakeholders:** whether a credit should be restricted to skills the collaborator actually
  lists (today the owner may name any skill, with the universal-region fallback above); whether a
  declined credit should be re-requestable by the owner or stay refused; video length limits for the
  media gallery (today only file size is capped, at 50 MB); AR copy for the new sections.
