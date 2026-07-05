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

## Not yet built (Phase 1B+)

- **Brand & satellites** (Phase 1B): `brands` and its satellites (`brand_aesthetics`, `brand_images`,
  `brand_creative_needs`, `brand_credibility`, `brand_reviews`, `brand_social_handles`,
  `brand_signals`).
- **Deal engine:** `deal_flows`, `deal_flow_steps`, `deals`, `deal_steps`, `deal_messages`,
  `deal_enquiries`.
- **Campaigns:** `campaigns`, `campaign_talent_types`, `campaign_media`.
- **Platform:** `settings`.

> `App\Models\Brand` remains a Phase 0 Authenticatable stub until Phase 1B.

## Open schema items (from the specs / decisions)
- `deals.campaign_id` — FK deals → campaigns (ADR-F), added when the campaign⇄deal link is finalised.
- Discovery/search: query-critical arrays promoted to indexed columns/pivots (ADR-6). Equipment and
  software_stack are already tables with indexes; brand-side promotions land in Phase 1B.
