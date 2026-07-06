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
(1:1, unique `talent_id`), `look_types`, `digitals`, `showreels`, `equipment`, `case_studies`,
`software_stack`, `agency_affiliations`, `press_features`.

Deviations from the canonical schema (deliberate, per the standing decisions):

- **Uploaded-asset URL columns are dropped.** `hero_image_url`, `avatar_url`, portfolio & digital
  `media_url`/`thumbnail_url`, `brand_logo_url`, `reviewer_avatar_url`, showreel/press `thumbnail_url`,
  `cover_image_url`, `icon_url`, `agency_logo_url` are **not** columns — they are media-library
  accessors on the models (ADR-5). Plain URL columns are kept only for EXTERNAL links/embeds:
  `showreels.video_url`, `brand_collabs.url`, `case_studies.url`, `agency_affiliations.agency_url`,
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

## Not yet built (Phase 1B+)

- **Brand core & satellites** (Phase 1B): extend `brands` (industry, stage, location, reach, …) and add
  `brand_aesthetics`, `brand_images`, `brand_creative_needs`, `brand_credibility`, `brand_reviews`,
  `brand_social_handles`, `brand_signals`.
- **Campaigns:** `campaigns`, `campaign_talent_types`, `campaign_media`.
- **Platform:** `settings`.

> `App\Models\Brand` is now a minimal deal-engine model (auth + identity + `is_complete`); Phase 1B
> fills out the rich brand profile.

## Open schema items (from the specs / decisions)
- `deals.campaign_id` — FK deals → campaigns (ADR-F), added when the campaign⇄deal link is finalised.
- Discovery/search (ADR-6): **applied for the talent side** in Phase 1C — see "Discovery search
  indexes" above. Brand-side promotions (`brand_creative_needs.talent_types`, aesthetic tags) land in
  Phase 1B.
