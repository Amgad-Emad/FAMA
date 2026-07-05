# Schema

> **Canonical model:** `docs/specs/schema-master.md`. That file is the single source of truth for every
> table, column, type, and relationship. This file tracks **what is actually migrated right now** and
> the plan to reach the canonical schema. When they differ, the spec wins — reconcile deliberately and
> record it in `docs/changelog.md`.

## Migrated in Phase 0 (vendor / auth / infrastructure only)

| Table | Source | Purpose |
|---|---|---|
| `users` | framework | Admin / platform staff (the `admin` guard). |
| `password_reset_tokens` | framework | Password resets. |
| `sessions` | framework | Session storage. |
| `cache`, `cache_locks` | framework | Cache store. |
| `jobs`, `job_batches`, `failed_jobs` | framework | Queues. |
| `personal_access_tokens` | laravel/sanctum | Mobile API tokens. |
| `media` | spatie/laravel-medialibrary | Uploaded files (collections + conversions). |
| `activity_log` | spatie/laravel-activitylog | Audit trail. |

`spatie/laravel-model-states` has **no** migration (state is stored in a normal column on each stateful
model, added with that model in Phase 1).

## Not yet built (feature tables — Phase 1+)

Everything in `docs/specs/schema-master.md` §1–§5 that isn't listed above:
- **Talent & block system** (Phase 1A): `talents`, `talent_types`, `talent_talent_type`, `block_types`,
  `block_type_category`, `profile_blocks`, and the talent content tables.
- **Brand & satellites** (Phase 1B): `brands`, `brand_aesthetics`, `brand_images`,
  `brand_creative_needs`, `brand_credibility`, `brand_reviews`, `brand_social_handles`, `brand_signals`.
- **Deal engine**: `deal_flows`, `deal_flow_steps`, `deals`, `deal_steps`, `deal_messages`,
  `deal_enquiries`.
- **Campaigns**: `campaigns`, `campaign_talent_types`, `campaign_media`.
- **Platform**: `settings` (users/activity_log already migrated).

> `App\Models\Brand` and `App\Models\Talent` exist now as **Authenticatable stubs** so their guards
> resolve and Sanctum can target them; their tables and full models arrive in Phase 1.

## Open schema items (from the specs)
- `deals.campaign_id` — FK deals → campaigns is referenced by brand/campaign workflows but not yet in
  the `deals` definition. Add when the campaign⇄deal link is finalised.
- **Discovery / search** — the discovery feed and talent search need filterable/indexed columns; flag
  additions when Phase 1 lands.

See `docs/decisions.md` for the reasoning behind deferrals.
