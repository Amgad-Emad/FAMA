# Schema

> **Canonical model:** `docs/specs/schema-master.md`. That file is the single source of truth for every
> table, column, type, and relationship. This file tracks **what is actually migrated right now** and
> the plan to reach the canonical schema. When they differ, the spec wins — reconcile deliberately and
> record it in `docs/changelog.md`.

## Vendor / auth / infrastructure (Phase 0)

| Table | Source | Purpose |
|---|---|---|
| `users` | framework | Admin / platform staff (the `admin` guard). |
| `password_reset_tokens`, `sessions` | framework | Password resets, session storage. |
| `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` | framework | Cache & queues. |
| `personal_access_tokens` | laravel/sanctum | Mobile API tokens. |
| `media` | spatie/laravel-medialibrary | Uploaded files (collections + conversions). |
| `activity_log` | spatie/laravel-activitylog | Audit trail. |

## Talent side + block system (Phase 1A — migrated)

**Core & block system:** `talents` (soft deletes), `talent_types`, `talent_talent_type`
(unique `talent_id`+`talent_type_id`), `specialities` (+ `speciality_talent`, `project_speciality` —
ADR-Y; `slug` unique per skill, not globally), `block_types`, `block_type_category`,
`block_type_talent_type`, `profile_blocks` (+ `block_types.is_profile_default` — ADR-Z),
`talent_social_handles`, `recognitions`, `talent_interests` (the ADR-Z portfolio sections),
`project_credits` (collaborator credits — ADR-AA; `projects` also gains `occurred_on`, with `year`
demoted to a derived projection).

**Content tables:** `portfolio_items`, `brand_collabs`, `reviews`, `comp_cards`
(1:1, unique `talent_id`), `look_types`, `digitals`, `showreels`, `equipment`, `projects` (+ `project_talent_type`),
`software_stack`.

> **Removed features (ADR-K/L/M):** `services`, `agency_affiliations`, `press_features` (tables +
> models + block types), the `talents` columns `availability_status` / `rate_tier` /
> `willing_to_travel` / `travel_regions`, and `contracts.service_id` / `contract_enquiries.service_id` were
> dropped by the `2026_07_10_0001–0004` migrations.

Deviations from the canonical schema (deliberate, per the standing decisions):

- **Uploaded-asset URL columns are dropped.** `avatar_url`, portfolio & digital
  `media_url`/`thumbnail_url`, `brand_logo_url`, `reviewer_avatar_url`, showreel `thumbnail_url`,
  `cover_image_url`, `icon_url` are **not** columns — they are media-library
  accessors on the models (ADR-5). Plain URL columns are kept only for EXTERNAL links/embeds:
  `showreels.video_url`, `brand_collabs.url`, `projects.url`, and a new
  `portfolio_items.embed_url` (for `media_type = embed`). *(The talent `hero`/cover collection +
  `hero_image_url` accessor were removed with the IG-style header — ADR-O; the `avatar` collection stays.)*
- **Translatable columns are JSON** (per-locale), not VARCHAR/TEXT — see the list in
  `docs/conventions.md`.
- **`block_type_talent_type`** pivot added to make `availability = by_type` functional (the spec pairs
  it with `block_type_category`).
- **Profile identity/settings are nullable** on `talents` (display_name, headline, bio, base_city,
  base_country, booking_value) so a talent can sign up first and fill the profile
  progressively; `slug` is auto-generated if not supplied (shown as **Username** in the UI — ADR-N).
- **Pricing rate (ADR-N):** `rate_unit` ENUM(project, day, hour) NULL, `rate_amount` DECIMAL(10,2) NULL,
  `rate_currency` CHAR(3) NULL added to `talents` (migration `2026_07_10_000500`). All-or-nothing, NOT
  translatable. Replaces the removed rate card (ADR-K).
- `profile_blocks.block_type_id` is **restrict-on-delete** (deactivated block types are grandfathered,
  not deleted); all `talent_id` FKs cascade.
- **`contract_templates` (2026-08-03, ADR-V):** admin-authored contract "schema" — `name`, `slug`,
  `applies_to` (nullable category), `is_active`/`is_default`, terms params (`deposit_pct`,
  `deposit_holder`, `cancellation_forfeit_pct`, `revision_rounds`, `preview_currency`), `intro`, and a
  `clauses` JSON (ordered `{label, body}` with numeric tokens). Migration `2026_08_03_000100` also adds
  `contract_flows.contract_template_id` (nullable FK, nullOnDelete) and `contracts.terms_snapshot` (JSON —
  the frozen template terms at contract creation). Morph alias `contract_template`.
- **Soft deletes on `reviews` (2026-07-31):** `deleted_at` (migration `2026_07_31_000100`) so an admin
  can remove a review with an emailed reason notice while preserving the row for audit.
- **`talents.is_verified` (2026-08-02, ADR-U):** boolean, default false (migration `2026_08_02_000100`,
  mirrors `brands.is_verified`). An admin sign-off that is now a **hard prerequisite for going public** —
  the `ToLive` (talent) / `ToPublished` (brand) transitions refuse to publish an unverified account. The
  migration backfills `true` for already-live talents / published brands.
- **State machine columns (Phase 1B):** `status` added to `talents`, `profile_blocks`, `reviews`,
  `portfolio_items` (spatie/laravel-model-states). The existing booleans are kept as synced projections
  (see `docs/architecture.md` → state machines table).
- **Skill scope (ADR-Q, migration `2026_07_11_000200`):** `profile_blocks.talent_type_id` and
  `projects.talent_type_id` — nullable FKs → `talent_types` (nullOnDelete). NULL = profile-level /
  universal; NOT NULL = the skill's tab. Indexes: `profile_blocks (talent_id, talent_type_id, position)`
  (position is per-scope) and `projects (talent_type_id)`. The migration **backfills**: projects → the
  talent's primary skill; blocks → the single skill their gate matches (universal/ambiguous → NULL), and
  reports the counts. Seeding is now per-skill (`SeedBlocksForSkill`).
- **Project skill tags (ADR-X, migration `2026_08_18_000100`):** `project_talent_type`
  (`project_id` → projects cascade, `talent_type_id` → talent_types cascade, `position` = tag order,
  unique `(project_id, talent_type_id)`, index on `talent_type_id`). A project carries **one or more**
  skills; `projects.talent_type_id` remains the **home tab** and is kept equal to tag 0, so every ADR-Q
  reader is unchanged. The tab test matches the home column **OR** a tag, so a column-only row (factory,
  muted seeder, legacy data) still renders. The migration backfills tag 0 from the existing scope column.
  Enforced at the one authoring surface (`TalentProjectService` + `StoreProjectRequest`).

## Discovery search indexes (Phase 1C — migrated, ADR-6)

`2026_07_06_000100_add_discovery_search_indexes` adds the indexes the public discovery/search page
filters on. The query-critical dimensions were already relational (skills via the
`talent_talent_type` pivot; gear/tools as the `equipment` and `software_stack` tables), so no arrays
needed promoting on the talent side — only these indexes:

- `talents`: `is_published`, `base_city`, `base_country` (single-column). *(The `availability_status`
  index was dropped with the column — ADR-L.)*
- `talent_talent_type`: `talent_type_id` (reverse pivot lookup — "talents who work as type X"; the
  existing unique index is `talent_id`-first).
- `equipment`: `category`; `software_stack`: `software_name` (cross-talent gear/tool filters; the
  existing composite indexes are `talent_id`-first).
- `look_types`: **functional index** `look_types_name_en_index` on `CAST(name->>'$.en' AS CHAR(191))`
  (migration `2026_07_11_000100`). `name` is a translatable JSON column, so it can't be indexed directly;
  the model-scope **Looks** filter matches the English name path, which this index covers. **Created only
  on genuine MySQL 8.0.13+** (functional key parts); **skipped on MariaDB** (no expression indexes — Laravel
  reports MariaDB as the `mysql` driver, so the migration inspects `VERSION()` to detect it) and on older
  MySQL. The Looks filter still works unindexed there — `look_types` is a tiny lookup table. (Comp-card
  attribute *ranges* are a noted future enhancement — not built.)

Consumed by `App\Queries\TalentSearch` (spatie/laravel-query-builder) via `filter[type|category|
city|country|equipment|software|looks|q]`.

## Contract engine (Phase 1E — migrated)

**Templates:** `contract_flows` (named, `applies_to` category scope, `is_default`), `contract_flow_steps`
(ordered; `actor`, `step_type`, `is_required`/`is_skippable`, `settings` JSON).

**Instances:** `contracts` (soft deletes; `reference` unique, FK brand/talent/contract_flow;
`current_step_id` → contract_steps; `status` state machine; headline brief/amount/dates; `initiated_by`),
`contract_steps` (per-contract snapshot; `status` state machine; `payload` JSON; polymorphic `completed_by`),
`contract_messages` (thread; `type` message/system_event/action_summary; polymorphic `sender`; `status`
sent→read state machine + `read_at` projection), `contract_enquiries` (pre-auth Contact capture; converts
to a contract, `converted_contract_id`).

Deviations (deliberate):

- **`brands` is a MINIMAL stub** (`create_brands_stub_table`): auth surface + `name`/`slug` +
  `is_complete` contract gate + flags, so `contracts.brand_id` can FK and tests can seed brands. The full brand
  core & satellites (schema-master §4) are Phase 1B and **extend** this table.
- **`contract_steps` snapshots `settings` + `is_required` + `is_skippable`** (not in schema-master's column
  list). Required by ADR-4 — the handler config must be frozen at creation so template edits never
  change an in-flight contract. `settings.instructions` carries the step's help text.
- **`contract_messages.status`** (sent/read) is the ContractMessage state-machine column; `read_at` is its
  synced projection (same convention as the Phase 1B state columns).
- **`brand_project_id`** on contracts has **landed** (ADR-F): nullable FK contracts → projects,
  so a project groups the contracts run under it.

## Brand core & satellites (Phase 2A — migrated)

`extend_brands_for_profile` fills the `brands` stub with the full identity (all nullable for
progressive onboarding): `description` (translatable), `industry`, `brand_stage`, `base_city`,
`base_country`, `geographic_reach`, `founded_year`, `company_size`, `website` (+ discovery indexes on
industry/reach/city/published). Logo & cover are medialibrary collections (ADR-5), not columns.

**Satellites:** `brand_aesthetics` (1:1; free-text `brand_references`), `brand_images` (child media),
`brand_creative_needs` (1:1; `project_frequency`, internal `budget_tier`), `brand_credibility` (1:1;
denormalized counters, internal `brief_quality_score`), `brand_reviews` (talent→brand, three
sub-ratings + `is_approved` + `status`), `brand_social_handles`, `brand_signals` (**append-only** — no
`updated_at`).

**ADR-6 promotions (brand side, applied):**
- `brand_aesthetics.mood_tags` → **`brand_mood_tags`** (`brand_aesthetic_id`, `tag`; unique + `tag`
  index) — "brands with an editorial mood".
- `brand_creative_needs.talent_types` → **`brand_creative_need_talent_type`** (M:N with `talent_types`)
  — "all brands needing photographers".
- `brand_creative_needs.project_types` → **`brand_creative_need_project_type`** (`project_type` enum,
  unique + index).

> `brand_signals` is an append-only event log and a candidate for a dedicated analytics store if
> volume grows (schema-master §4).

## Projects (Phase 2A — migrated)

`brand_projects` (soft deletes; `slug` unique, `type` project/shoot, `status` draft/open/in_progress/
completed/cancelled, budget min/max + currency, `budget_is_public`, location, dates, `is_public`,
`talent_type_id`; `description` translatable; cover via medialibrary), `brand_project_media` (gallery;
uploads via medialibrary, `embed_url` external, `caption` translatable).

- **One role, one position.** `brand_projects.talent_type_id` (nullable FK → `talent_types`) replaces the
  removed `brand_project_talent_types` pivot and its `quantity`/`positions_count` columns: a project seeks
  exactly one discipline for exactly one position.
- **`budget_is_public`** (boolean, default **false**) — the budget is private by default. When false,
  `budget_min`/`budget_max` are withheld from every non-owning viewer (public profile, project detail,
  talent-facing opportunity feed); only the owning brand sees them.

## Platform & admin (Phase 3A — migrated)

- **`users` refined** (schema-master §6): added `phone`, `avatar_url`, `locale` enum(en, ar),
  `last_login_at`, `is_active` (default true), and **soft deletes** (`deleted_at`). No `role` column —
  admin roles are modelled with spatie/laravel-permission (ADR-H).
- **RBAC via spatie/laravel-permission** on the **`admin`** guard: `roles`, `permissions`,
  `model_has_roles`, `model_has_permissions`, `role_has_permissions`. Seeded roles **super-admin /
  moderator / support** and permissions **manage-flows, moderate-content, intervene-contracts,
  manage-settings, manage-users** (`RolesAndPermissionsSeeder`). `User` uses `HasRoles` with
  `$guard_name = 'admin'`.
- **`settings`** — key (unique) → value (JSON), read/written through `App\Services\SettingsService`
  (cached key→value map; typed globals: default currency, default contract flow, feature flags). Seeded by
  `SettingsSeeder`.
- **`activity_log`** (already installed, Phase 0) — records **subject + causer + changes**.
  `ContractFlow`/`ContractFlowStep` apply `LogsActivity` with `getActivitylogOptions()` (log name
  `contract_flow`, `logOnlyDirty()` + `dontLogEmptyChanges()`), so every admin edit to a flow template is
  audited — a flow is snapshotted into `contract_steps`, so an edit here reshapes future contracts.
  The causer resolves from the **`admin`** guard (`activitylog.default_auth_driver` is null → the default
  guard, which `config/auth.php` sets to `admin`). Note: this activitylog version (5.0) stores model
  old/new under **`attribute_changes`** (collection); `properties` holds ad-hoc custom data. The
  `LogOptions` method is `dontLogEmptyChanges()` — there is no `dontSubmitEmptyLogs()`.

## Feed (Phase 5B — migrated)

- **Ten feed tables** (`2026_07_22_000100`, schema-master §7, ADR-F1…F8): `posts` (morph author;
  `post_type` enum note/media/project_share; `body` longText; `project_id` → projects nullOnDelete +
  `shared_snapshot` JSON — ADR-F2; `state` for PostState; counters; `published_at`/`edited_at`/
  `is_pinned`; soft deletes; indexes `(author_type, author_id, published_at)`, `(state, published_at)`,
  `(project_id)`), `post_skill` (composite-PK pivot to `talent_types`), `comments` (self `parent_id`,
  depth ≤ 2 — ADR-F7; morph author; counters; CommentState; soft deletes; index
  `(post_id, parent_id, created_at)`), `likes` (double morph, `likes_unique` — the ToggleLike
  idempotency backstop), `follows` (double morph, append-only `created_at`, `follows_unique`),
  `mentions` (double morph + `start_offset`/`length` — ADR-F3), `link_previews` (`url_hash` CHAR(64)
  unique; parse fields + enrichment fields; status pending/ok/failed — ADR-F4), `linked_urls` (morph
  linkable → preview, positioned), `post_reports` (morph reportable post|comment + morph reporter;
  reason string(40) keyed to config `feed.report_reasons` (widened from enum, 2026_07_22_000600) + status enum; `resolved_by` → users; one-per-reporter unique), `feed_seen` (morph viewer ×
  post, `seen_at`, unique + `(viewer_type, viewer_id, seen_at)` index).
- **Counter columns** (ADR-F6): `talents.followers_count/following_count/posts_count`,
  `brands.following_count` — mutated only via `CounterService` from observers (Like/Comment/Follow/
  PostObserver) inside the writing transaction; clamped at zero; reconciled by
  **`feed:reconcile-counters`** (scheduled daily 03:10; `comments.replies_count` reconciles through a
  derived-table join — MySQL 1093). `posts_count` counts **currently-published, non-deleted** posts.
- **Enforced aliased morph map** (`2026_07_22_000200`, ADR-F1): `Relation::enforceMorphMap` over
  `App\Support\MorphMap` — every model, aliased snake_case (`talent`, `brand`, `user`, `post`,
  `comment`, `project`, …, plus `media`/`role`/`permission`). The data migration rewrote legacy FQCN
  morph values in `media`, `activity_log`, `model_has_roles`, `model_has_permissions`,
  `personal_access_tokens`, `contract_steps`, `contract_messages`. **Aliases are frozen — changing one
  is a data migration.** After deploying, flush the spatie permission cache.

## Feed revisions (5X/5Z — migrated)

- **`bookmarks`** (5X): double-morph saves (`bookmarker` × `bookmarkable`), unique ×4, posts-only by
  policy; `posts.bookmarks_count` denormalized + reconciled. Collections are private (ADR-F9).
- **`suggestion_dismissals`** (5Z): `actor` × `suggestible` double morph, unique ×4, append-only —
  a dismissed follow suggestion never returns. **`brands.followers_count`** landed with
  `feed.allow_following_brands = true` (the schema-master §7 deliberate omission is closed);
  maintained by FollowObserver, reconciled nightly.

## Dashboard analytics (2026-08-10 — migrated)

- **`talent_profile_views`**: daily view buckets — `talent_id` FK (cascade), `viewed_on` date,
  `views` unsigned int, unique `(talent_id, viewed_on)`. Written by `RecordDailyProfileView` (atomic
  `ON DUPLICATE KEY UPDATE` increment per `TalentProfileViewed`); `talents.view_count` stays the
  lifetime total. Read by `ProfileViewStatsService` for the dashboard views KPI (week-over-week
  delta) + expandable chart (`GET /talent/dashboard/views`). No history backfill — the series starts
  the day the table shipped.

## Not yet built

- **Mobile API** (Phase 4) — Sanctum tokens + Scribe docs over the existing services/resources.
- **Feed HTTP layer + notifications tables** — land with Prompts 5C–5K (Laravel `notifications`
  table migration ships with 5J).

## Open schema items (from the specs / decisions)
- Discovery/search (ADR-6): **applied for both talent (Phase 1C) and brand (Phase 2A)** sides.
