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
(unique `talent_id`+`talent_type_id`), `block_types`, `block_type_category`, `block_type_talent_type`,
`profile_blocks`.

**Content tables:** `portfolio_items`, `brand_collabs`, `reviews`, `comp_cards`
(1:1, unique `talent_id`), `look_types`, `digitals`, `showreels`, `equipment`, `projects`,
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
- **State machine columns (Phase 1B):** `status` added to `talents`, `profile_blocks`, `reviews`,
  `portfolio_items` (spatie/laravel-model-states). The existing booleans are kept as synced projections
  (see `docs/architecture.md` → state machines table).
- **Skill scope (ADR-Q, migration `2026_07_11_000200`):** `profile_blocks.talent_type_id` and
  `projects.talent_type_id` — nullable FKs → `talent_types` (nullOnDelete). NULL = profile-level /
  universal; NOT NULL = the skill's tab. Indexes: `profile_blocks (talent_id, talent_type_id, position)`
  (position is per-scope) and `projects (talent_type_id)`. The migration **backfills**: projects → the
  talent's primary skill; blocks → the single skill their gate matches (universal/ambiguous → NULL), and
  reports the counts. Seeding is now per-skill (`SeedBlocksForSkill`).

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

## Not yet built

- **Mobile API** (Phase 4) — Sanctum tokens + Scribe docs over the existing services/resources.

## Open schema items (from the specs / decisions)
- Discovery/search (ADR-6): **applied for both talent (Phase 1C) and brand (Phase 2A)** sides.
