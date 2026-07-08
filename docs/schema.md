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

**Content tables:** `portfolio_items`, `brand_collabs`, `reviews`, `services`, `comp_cards`
(1:1, unique `talent_id`), `look_types`, `digitals`, `showreels`, `equipment`, `projects`,
`software_stack`, `agency_affiliations`, `press_features`.

Deviations from the canonical schema (deliberate, per the standing decisions):

- **Uploaded-asset URL columns are dropped.** `hero_image_url`, `avatar_url`, portfolio & digital
  `media_url`/`thumbnail_url`, `brand_logo_url`, `reviewer_avatar_url`, showreel/press `thumbnail_url`,
  `cover_image_url`, `icon_url`, `agency_logo_url` are **not** columns — they are media-library
  accessors on the models (ADR-5). Plain URL columns are kept only for EXTERNAL links/embeds:
  `showreels.video_url`, `brand_collabs.url`, `projects.url`, `agency_affiliations.agency_url`,
  `press_features.url`, and a new `portfolio_items.embed_url` (for `media_type = embed`).
- **Translatable columns are JSON** (per-locale), not VARCHAR/TEXT — see the list in
  `docs/conventions.md`.
- **`block_type_talent_type`** pivot added to make `availability = by_type` functional (the spec pairs
  it with `block_type_category`).
- **Profile identity/settings are nullable** on `talents` (display_name, headline, bio, base_city,
  base_country, booking_value, rate_tier) so a talent can sign up first and fill the profile
  progressively; `slug` is auto-generated if not supplied.
- `profile_blocks.block_type_id` is **restrict-on-delete** (deactivated block types are grandfathered,
  not deleted); all `talent_id` FKs cascade.
- **State machine columns (Phase 1B):** `status` added to `talents`, `profile_blocks`, `reviews`,
  `services`, `agency_affiliations`, `portfolio_items` (spatie/laravel-model-states); `availability_status`
  is the availability state. The existing booleans are kept as synced projections (see
  `docs/architecture.md` → state machines table).

## Discovery search indexes (Phase 1C — migrated, ADR-6)

`2026_07_06_000100_add_discovery_search_indexes` adds the indexes the public discovery/search page
filters on. The query-critical dimensions were already relational (professions via the
`talent_talent_type` pivot; gear/tools as the `equipment` and `software_stack` tables), so no arrays
needed promoting on the talent side — only these indexes:

- `talents`: `availability_status`, `is_published`, `base_city`, `base_country` (single-column).
- `talent_talent_type`: `talent_type_id` (reverse pivot lookup — "talents who work as type X"; the
  existing unique index is `talent_id`-first).
- `equipment`: `category`; `software_stack`: `software_name` (cross-talent gear/tool filters; the
  existing composite indexes are `talent_id`-first).

Consumed by `App\Queries\TalentSearch` (spatie/laravel-query-builder) via `filter[type|category|
availability|city|country|equipment|software|q]`.

## Deal engine (Phase 1E — migrated)

**Templates:** `deal_flows` (named, `applies_to` category scope, `is_default`), `deal_flow_steps`
(ordered; `actor`, `step_type`, `is_required`/`is_skippable`, `settings` JSON).

**Instances:** `deals` (soft deletes; `reference` unique, FK brand/talent/service?/deal_flow;
`current_step_id` → deal_steps; `status` state machine; headline brief/amount/dates; `initiated_by`),
`deal_steps` (per-deal snapshot; `status` state machine; `payload` JSON; polymorphic `completed_by`),
`deal_messages` (thread; `type` message/system_event/action_summary; polymorphic `sender`; `status`
sent→read state machine + `read_at` projection), `deal_enquiries` (pre-auth Contact capture; converts
to a deal, `converted_deal_id`).

Deviations (deliberate):

- **`brands` is a MINIMAL stub** (`create_brands_stub_table`): auth surface + `name`/`slug` +
  `is_complete` deal gate + flags, so `deals.brand_id` can FK and tests can seed brands. The full brand
  core & satellites (schema-master §4) are Phase 1B and **extend** this table.
- **`deal_steps` snapshots `settings` + `is_required` + `is_skippable`** (not in schema-master's column
  list). Required by ADR-4 — the handler config must be frozen at creation so template edits never
  change an in-flight deal. `settings.instructions` carries the step's help text.
- **`deal_messages.status`** (sent/read) is the DealMessage state-machine column; `read_at` is its
  synced projection (same convention as the Phase 1B state columns).
- **`campaign_id`** on deals is still deferred (ADR-F), added with campaigns.

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

## Campaigns (Phase 2A — migrated)

`campaigns` (soft deletes; `slug` unique, `type` campaign/shoot, `status` draft/open/in_progress/
completed/cancelled, budget min/max + currency, location, dates, `is_public`, `positions_count`;
`description` translatable; cover via medialibrary), `campaign_talent_types` (roles sought,
UNIQUE(campaign_id, talent_type_id), `quantity`), `campaign_media` (gallery; uploads via medialibrary,
`embed_url` external, `caption` translatable).

## Platform & admin (Phase 3A — migrated)

- **`users` refined** (schema-master §6): added `phone`, `avatar_url`, `locale` enum(en, ar),
  `last_login_at`, `is_active` (default true), and **soft deletes** (`deleted_at`). No `role` column —
  admin roles are modelled with spatie/laravel-permission (ADR-H).
- **RBAC via spatie/laravel-permission** on the **`admin`** guard: `roles`, `permissions`,
  `model_has_roles`, `model_has_permissions`, `role_has_permissions`. Seeded roles **super-admin /
  moderator / support** and permissions **manage-flows, moderate-content, intervene-deals,
  manage-settings, manage-users** (`RolesAndPermissionsSeeder`). `User` uses `HasRoles` with
  `$guard_name = 'admin'`.
- **`settings`** — key (unique) → value (JSON), read/written through `App\Services\SettingsService`
  (cached key→value map; typed globals: default currency, default deal flow, feature flags). Seeded by
  `SettingsSeeder`.
- **`activity_log`** (already installed, Phase 0) — confirmed recording **subject + causer + changes**;
  `DealFlow`/`DealFlowStep` now use `LogsActivity` (log name `deal_flow`) so the coming admin authoring
  layer is audited. Note: this activitylog version stores model old/new under **`attribute_changes`**
  (collection); `properties` holds ad-hoc custom data.

## Not yet built

- **Admin authoring/moderation UI** (Phase 3A cont.) — the flow-builder, moderation queues, and deal-step
  intervention screens sit on top of the schema above.

## Open schema items (from the specs / decisions)
- `deals.campaign_id` — FK deals → campaigns (ADR-F), added when the campaign⇄deal link is finalised
  (campaigns now exist, so this can land in the brand deal phase, 2C).
- Discovery/search (ADR-6): **applied for both talent (Phase 1C) and brand (Phase 2A)** sides.
