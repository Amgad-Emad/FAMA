# Fama — Database Schema (Canonical)

> Single source of truth for the Fama data model. Transcribed and lightly cleaned (typos fixed, ordering and formatting normalized) from the original design docs — **no technical content changed**. Tables are grouped by domain for readability; grouping is presentational only.
>
> **Implementation note:** per `docs/decisions.md`, all **uploaded** files go through `spatie/laravel-medialibrary` (collections + conversions). The `*_url` / `thumbnail_url` columns below for *uploaded* assets are replaced at implementation time by media-library accessors; plain URL columns are kept only for **external** links/embeds (YouTube/Vimeo, social, brand-collab, press). The schema is documented here as originally designed.

---

## 1. Talent core & block system

### `talents`
The profile itself — the "living creative passport." Holds one-per-profile identity data (name, bio, hero image, public slug) and the singular settings that only ever have one value per person (availability, rate tier, travel, booking method). Anything that is "one talent = one value" lives here rather than in a child table. A talent's profession(s) are linked through `talent_talent_type`, and the merged `default_blocks` of those types decide the default layout. Soft deletes let you unpublish/remove without losing history.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| email | VARCHAR, UNIQUE |
| password | VARCHAR |
| email_verified_at | TIMESTAMP NULL |
| phone | VARCHAR NULL |
| remember_token | VARCHAR NULL |
| last_login_at | TIMESTAMP NULL |
| is_active | BOOLEAN |
| slug | VARCHAR, UNIQUE — `fama.com/{slug}` |
| display_name | VARCHAR |
| headline | VARCHAR |
| bio | TEXT |
| hero_image_url | VARCHAR |
| avatar_url | VARCHAR |
| availability_status | ENUM(available, booked, unavailable) |
| base_city | VARCHAR |
| base_country | VARCHAR |
| rate_tier | ENUM(emerging, established, premium, elite) NULL |
| willing_to_travel | BOOLEAN |
| travel_regions | JSON NULL |
| booking_type | ENUM(email, calendar, form, external) |
| booking_value | VARCHAR |
| is_published | BOOLEAN |
| published_at | TIMESTAMP NULL |
| view_count | UNSIGNED INT |
| meta | JSON NULL |
| created_at / updated_at / deleted_at | timestamps + soft delete |

### `talent_types`
Lookup table for the six professions (model, photographer, DOP, creative director, stylist, graphic designer). Its real job is `default_blocks`: when a new talent picks "photographer," this is where the system reads which blocks to preload and in what order. Keeping it as a table (not hardcoded) means you can add a profession or change a default layout without touching code or migrations.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| name | VARCHAR |
| slug | VARCHAR, UNIQUE |
| category | ENUM(model, crew, creative) |
| default_blocks | JSON |
| icon | VARCHAR |
| description | VARCHAR |
| created_at / updated_at | timestamps |

### `talent_talent_type` (pivot)
Makes talent ↔ profession many-to-many, so one talent can be more than one type (e.g. photographer + creative director, or model + photographer). `is_primary` marks the type that leads the profile and drives the headline; `position` orders the rest. On profile creation the system merges the `default_blocks` of every linked type and dedupes before seeding `profile_blocks`, so a multi-type talent gets no duplicate blocks. `UNIQUE(talent_id, talent_type_id)` stops the same type being attached twice.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| talent_id | FK → talents |
| talent_type_id | FK → talent_types |
| is_primary | BOOLEAN |
| position | UNSIGNED INT |
| created_at / updated_at | timestamps |
| — | UNIQUE(talent_id, talent_type_id) |

### `block_types`
Each row is a block the platform offers. `availability` is the key field — whether every talent can have it, or only certain categories/types.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| key | VARCHAR, UNIQUE (hero, gallery, comp_card, showreel…) |
| name | VARCHAR (display) |
| description | VARCHAR |
| icon | VARCHAR |
| availability | ENUM(universal, by_category, by_type) — universal = any talent; otherwise gated |
| content_source | ENUM(inline, table) — inline JSON vs a linked child table |
| default_layout | ENUM(grid, carousel, list, masonry) NULL |
| is_active | BOOLEAN — admin can switch a block off platform-wide |
| is_repeatable | BOOLEAN — can a profile have more than one? |
| position | UNSIGNED INT — default order in the picker |
| settings_schema | JSON NULL — what config the block accepts |
| created_at / updated_at | timestamps |

### `block_type_category`
Which categories a gated block applies to. Only used when `availability = by_category` (pair with a `block_type_talent_type` pivot for `by_type` granularity).

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| block_type_id | FK → block_types |
| category | ENUM(model, crew, creative) |

### `profile_blocks`
The heart of the "malleable, reorderable" system. Every block a talent shows is a row here, with `position` for ordering, `is_visible` for show/hide, and `block_type_id` to say what it is. This table is layout and arrangement, deliberately separated from the actual content: simple blocks store data inline in `content` (JSON); rich blocks point to their own tables. This separation lets a talent drag, hide, and rearrange blocks without restructuring data.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| talent_id | FK → talents |
| block_type_id | FK → block_types |
| title | VARCHAR |
| position | UNSIGNED INT |
| is_visible | BOOLEAN |
| layout | ENUM(grid, carousel, list, masonry) NULL |
| settings | JSON |
| content | JSON NULL |
| created_at / updated_at | timestamps |

---

## 2. Talent content tables

### `portfolio_items`
The gallery — one row per image/video. Its own table because a profile has many media items and you'll want to paginate, reorder, and attach credits/tags per item. `block_id` links each item to the gallery block it belongs to, so a talent could even have more than one gallery.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| talent_id | FK → talents |
| block_id | FK → profile_blocks NULL |
| media_type | ENUM(image, video, embed) |
| media_url | VARCHAR |
| thumbnail_url | VARCHAR |
| caption | VARCHAR |
| credits | JSON |
| tags | JSON |
| position | UNSIGNED INT |
| created_at / updated_at | timestamps |

### `brand_collabs`
Past brand work, one row per collaboration. Separate table because it's a growing list (many per talent) and you may want to show logos, sort by year, or filter.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| talent_id | FK → talents |
| brand_name | VARCHAR |
| brand_logo_url | VARCHAR |
| project_title | VARCHAR |
| year | SMALLINT |
| url | VARCHAR |
| position | UNSIGNED INT |
| created_at / updated_at | timestamps |

### `reviews`
Client/peer testimonials, one per review. Needs its own table for the one-to-many relationship plus moderation: `is_approved` gates reviews before they go public, and `rating` lets you compute averages.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| talent_id | FK → talents |
| reviewer_name | VARCHAR |
| reviewer_role | VARCHAR |
| reviewer_company | VARCHAR |
| reviewer_avatar_url | VARCHAR |
| rating | TINYINT (1–5) |
| body | TEXT |
| project_type | VARCHAR |
| is_approved | BOOLEAN |
| reviewed_at | TIMESTAMP |
| created_at / updated_at | timestamps |

### `services`
What the talent offers and for how much. Separate because a talent has multiple services, each with its own price, unit (hour/day/project), and active flag.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| talent_id | FK → talents |
| name | VARCHAR |
| description | TEXT |
| price | DECIMAL(10,2) NULL |
| currency | CHAR(3) |
| price_unit | ENUM(hour, day, project, fixed) |
| duration_minutes | INT NULL |
| is_active | BOOLEAN |
| position | UNSIGNED INT |
| created_at / updated_at | timestamps |

### `comp_cards`
Model-specific stats (height, measurements, hair/eye color). A 1:1 table (note the UNIQUE on `talent_id`) rather than columns on `talents`, because these fields only apply to models — putting them on `talents` would leave them NULL for every photographer and designer.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| talent_id | FK → talents, UNIQUE |
| height_cm | SMALLINT |
| bust_cm | SMALLINT |
| waist_cm | SMALLINT |
| hips_cm | SMALLINT |
| shoe_size | VARCHAR |
| dress_size | VARCHAR |
| hair_color | VARCHAR |
| eye_color | VARCHAR |
| skin_tone | VARCHAR |
| measurements | JSON |
| created_at / updated_at | timestamps |

### `look_types`
The "looks" a model can do (editorial, commercial, runway, etc.). A child table because it's a many list per model and editable/reorderable.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| talent_id | FK → talents |
| name | VARCHAR |
| position | UNSIGNED INT |
| created_at / updated_at | timestamps |

### `digitals`
A model's polaroids/digitals. Separate from `portfolio_items` because they're a distinct industry concept (unretouched reference shots, organized by `shot_type`) rather than curated portfolio work.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| talent_id | FK → talents |
| media_url | VARCHAR |
| thumbnail_url | VARCHAR |
| shot_type | ENUM(front, side, back, full, headshot, smile) |
| captured_at | DATE |
| position | UNSIGNED INT |
| created_at / updated_at | timestamps |

### `showreels`
Video reels for crew/creative talent. Its own table because there can be several, each from a different platform with its own thumbnail and duration. `platform` as an enum lets the frontend embed correctly per source.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| talent_id | FK → talents |
| title | VARCHAR |
| video_url | VARCHAR |
| platform | ENUM(youtube, vimeo, self_hosted) |
| thumbnail_url | VARCHAR |
| duration_seconds | INT |
| position | UNSIGNED INT |
| created_at / updated_at | timestamps |

### `equipment`
A crew member's kit (cameras, lenses, lighting). One row per item, grouped by category, because a kit is a long, growing list and clients often filter by gear ("who owns a RED camera").

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| talent_id | FK → talents |
| category | ENUM(camera, lens, lighting, audio, grip, drone, accessory) |
| brand | VARCHAR |
| model | VARCHAR |
| name | VARCHAR |
| notes | VARCHAR |
| position | UNSIGNED INT |
| created_at / updated_at | timestamps |

### `case_studies`
Long-form project write-ups for creatives. Each is a substantial content object (summary, full body, cover image, measurable results). The `results` JSON holds flexible metrics per case without needing fixed columns.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| talent_id | FK → talents |
| title | VARCHAR |
| client_name | VARCHAR |
| role | VARCHAR |
| summary | VARCHAR |
| body | LONGTEXT |
| cover_image_url | VARCHAR |
| results | JSON |
| year | SMALLINT |
| url | VARCHAR |
| position | UNSIGNED INT |
| created_at / updated_at | timestamps |

### `software_stack`
Tools a creative uses, with proficiency level. A child table because it's a many list and you may want to filter ("designers who know Figma").

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| talent_id | FK → talents |
| software_name | VARCHAR |
| proficiency | ENUM(beginner, intermediate, advanced, expert) |
| icon_url | VARCHAR |
| position | UNSIGNED INT |
| created_at / updated_at | timestamps |

### `agency_affiliations`
Optional representation info. A talent can have several (different agencies in different regions), each with its own representation type and `is_current` flag — handles the "represented by X in Europe, Y in the US" case cleanly.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| talent_id | FK → talents |
| agency_name | VARCHAR |
| agency_url | VARCHAR |
| agency_logo_url | VARCHAR |
| representation_type | ENUM(exclusive, non_exclusive, mother_agency, freelance) |
| region | VARCHAR |
| is_current | BOOLEAN |
| created_at / updated_at | timestamps |

### `press_features`
Media mentions and press — a growing list, sortable by date, each with a link and thumbnail.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| talent_id | FK → talents |
| publication | VARCHAR |
| title | VARCHAR |
| url | VARCHAR |
| thumbnail_url | VARCHAR |
| published_date | DATE |
| position | UNSIGNED INT |
| created_at / updated_at | timestamps |

---

## 3. Deal engine

### `deal_flows`
The named, reusable step sequence the admin builds. Kept as a table (not hardcoded), same pattern as `talent_types.default_blocks` — admin can create or change a flow without code or migrations. `applies_to` scopes a flow to a category (e.g. a different flow for models vs crew) or is left null for all.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| name | VARCHAR (e.g. "Standard Booking", "Quick Hire") |
| slug | VARCHAR, UNIQUE |
| description | VARCHAR |
| applies_to | ENUM(all, model, crew, creative) NULL |
| is_active | BOOLEAN |
| is_default | BOOLEAN |
| created_at / updated_at | timestamps |

### `deal_flow_steps`
One row per step in a flow. `actor` says who must act, `step_type` says what kind of interaction it is, and `settings` holds per-step config (required fields, payment %, contract template id). This is the table the admin flow-builder writes to.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| deal_flow_id | FK → deal_flows |
| key | VARCHAR (machine name: brief, quote, agreement, payment, delivery, review) |
| name | VARCHAR (display) |
| instructions | TEXT NULL |
| actor | ENUM(brand, talent, both, admin, system) |
| step_type | ENUM(form, approval, upload, payment, contract, message, schedule, info) |
| position | UNSIGNED INT |
| is_required | BOOLEAN |
| is_skippable | BOOLEAN |
| settings | JSON |
| created_at / updated_at | timestamps |

### `deals` (the engagement instance)
One row per brand ↔ talent deal. `current_step_id` points at the active step; `status` mirrors whose turn it is so the inbox can filter. The brief, agreed amount, and shoot dates live here as the deal's headline data.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| reference | VARCHAR, UNIQUE (human code, e.g. FAMA-2026-0001) |
| brand_id | FK → brands |
| talent_id | FK → talents |
| service_id | FK → services NULL |
| deal_flow_id | FK → deal_flows (the flow it was seeded from) |
| current_step_id | FK → deal_steps NULL |
| status | ENUM(draft, awaiting_brand, awaiting_talent, awaiting_admin, completed, cancelled, declined, expired) |
| title | VARCHAR |
| brief | TEXT NULL |
| agreed_amount | DECIMAL(10,2) NULL |
| currency | CHAR(3) |
| start_date | DATE NULL |
| end_date | DATE NULL |
| initiated_by | ENUM(brand, talent) |
| created_at / updated_at / deleted_at | timestamps + soft delete |

> **Open item:** the brand workflows reference `deals.campaign_id` (a deal running under a campaign). This FK is **not yet present** in the table above — tracked as an open decision. Add `campaign_id — FK → campaigns NULL` when the campaign↔deal link is finalized.

### `deal_steps` (per-deal snapshot of the flow steps — the progress tracker)
When a deal starts, the flow's steps are snapshotted here so editing the template later doesn't break in-flight deals. Each row tracks its own status and stores what was captured (`payload` = the quote amount, brief answers, uploaded-file references).

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| deal_id | FK → deals |
| flow_step_id | FK → deal_flow_steps NULL (origin reference) |
| key | VARCHAR (snapshotted) |
| name | VARCHAR (snapshotted) |
| actor | ENUM(brand, talent, both, admin, system) |
| step_type | ENUM(form, approval, upload, payment, contract, message, schedule, info) |
| position | UNSIGNED INT |
| status | ENUM(pending, active, awaiting_action, completed, skipped, rejected) |
| payload | JSON NULL |
| completed_by_type | VARCHAR NULL (morph: talents / brands / users) |
| completed_by_id | BIGINT UNSIGNED NULL (morph) |
| completed_at | TIMESTAMP NULL |
| created_at / updated_at | timestamps |

### `deal_messages` (the chat-like thread)
What makes the deal room look like a chat. Free-text messages and system events interleaved chronologically, optionally tied to a step. `type` lets "Talent accepted the quote" render as a system event alongside real messages, so the timeline reads naturally.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| deal_id | FK → deals |
| deal_step_id | FK → deal_steps NULL |
| sender_type | VARCHAR NULL (morph: talents / brands / users) — null for system |
| sender_id | BIGINT UNSIGNED NULL (morph) — null for system |
| sender_role | ENUM(brand, talent, admin, system) |
| type | ENUM(message, system_event, action_summary) |
| body | TEXT NULL |
| attachments | JSON NULL |
| read_at | TIMESTAMP NULL |
| created_at / updated_at | timestamps |

### `deal_enquiries` (no-login capture from the Contact button)
The pre-auth holding table. When a visitor presses Contact on a public talent profile, the enquiry lands here before any brand account exists. Once the visitor authenticates and completes their brand profile, the enquiry converts into a `deals` row and `converted_deal_id` is set.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| talent_id | FK → talents |
| service_id | FK → services NULL |
| contact_name | VARCHAR |
| contact_email | VARCHAR |
| contact_company | VARCHAR NULL |
| brief | TEXT |
| status | ENUM(new, converted, declined, expired) |
| converted_deal_id | FK → deals NULL |
| created_at / updated_at | timestamps |

---

## 4. Brand core & satellites

### `brands`
The brand-side identity — the foundation everything sits on. Holds the one-per-brand public-facing identity plus onboarding-collected basics. Admin-only fields (company size, website, founded year) are deferred to settings per the onboarding philosophy, so they're nullable. `is_complete` gates the Contact-to-deal flow.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| email | VARCHAR, UNIQUE |
| password | VARCHAR |
| email_verified_at | TIMESTAMP NULL |
| phone | VARCHAR NULL |
| remember_token | VARCHAR NULL |
| last_login_at | TIMESTAMP NULL |
| is_active | BOOLEAN |
| slug | VARCHAR, UNIQUE — `fama.com/brands/{slug}` |
| name | VARCHAR |
| logo_url | VARCHAR NULL |
| cover_image_url | VARCHAR NULL (dynamic aesthetic hero) |
| description | VARCHAR (one-sentence) |
| industry | ENUM(fashion, beauty, food_beverage, lifestyle, tech, other) |
| brand_stage | ENUM(new, growing, established) |
| base_city | VARCHAR |
| base_country | VARCHAR |
| geographic_reach | ENUM(same_city, mena, international) |
| founded_year | SMALLINT NULL (settings-stage) |
| company_size | ENUM(solo, small, medium, large, enterprise) NULL (settings-stage) |
| website | VARCHAR NULL (settings-stage) |
| is_complete | BOOLEAN (deal-flow gate) |
| is_verified | BOOLEAN |
| is_published | BOOLEAN |
| view_count | UNSIGNED INT |
| meta | JSON NULL |
| created_at / updated_at / deleted_at | timestamps + soft delete |

### `brand_aesthetics`
The creative direction — the richest discovery signal. One-to-one with the brand; kept separate because it's the discovery engine's core input and mixes structured tags with free text and images.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| brand_id | FK → brands, UNIQUE |
| mood_tags | JSON (editorial, minimal, bold, warm, dark, playful, luxurious, raw, nostalgic, commercial) |
| brand_references | TEXT NULL |
| created_at / updated_at | timestamps |

### `brand_images`
The 2–3 uploaded brand images. A child table (not JSON) because they're media you'll display and reorder, and they're a primary signal worth querying.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| brand_id | FK → brands |
| image_url | VARCHAR |
| thumbnail_url | VARCHAR |
| position | UNSIGNED INT |
| created_at / updated_at | timestamps |

### `brand_creative_needs`
What roles/projects they hire for and how often — drives feed weighting and brief pre-fill. One-to-one; arrays stored as JSON since they're multi-select preferences (promote to pivots if you need to query "all brands needing photographers").

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| brand_id | FK → brands, UNIQUE |
| talent_types | JSON (models, photographers, cinematographers, dps, stylists, creative_directors, graphic_designers) |
| project_types | JSON (editorial, lookbook, campaign_video, social_content, brand_identity) |
| project_frequency | ENUM(occasional, monthly, weekly, ongoing) NULL |
| budget_tier | ENUM(under_500, 500_2000, 2000_10000, 10000_plus) (internal-only) |
| created_at / updated_at | timestamps |

### `brand_credibility`
Outward trust signals, built over time. Denormalized counters (not computed live) so the profile reads cheaply. `brief_quality_score` is internal-only.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| brand_id | FK → brands, UNIQUE |
| completed_projects_count | UNSIGNED INT |
| avg_response_time_hours | DECIMAL(6,2) NULL |
| response_rate_pct | TINYINT NULL |
| brief_quality_score | DECIMAL(4,2) NULL (internal) |
| updated_at | timestamp |

### `brand_reviews`
Talent reviewing the brand — the mirror of `reviews`, and a key differentiator. Three sub-ratings (communication, fairness, creative respect) rather than one star.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| brand_id | FK → brands |
| talent_id | FK → talents |
| deal_id | FK → deals NULL (the completed project it's tied to) |
| communication_rating | TINYINT (1–5) |
| fairness_rating | TINYINT (1–5) |
| creative_respect_rating | TINYINT (1–5) |
| body | TEXT NULL |
| is_approved | BOOLEAN |
| created_at / updated_at | timestamps |

### `brand_social_handles` (settings-stage, optional)
Deferred per the onboarding philosophy, but kept for the profile's "Social Media Handles" feature. Populated in settings rather than onboarding.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| brand_id | FK → brands |
| platform | ENUM(instagram, tiktok, x, linkedin, youtube, facebook, behance, website, other) |
| handle | VARCHAR |
| url | VARCHAR |
| position | UNSIGNED INT |
| created_at / updated_at | timestamps |

### `brand_signals` (internal — discovery enrichment)
The implicit behaviour log: who they view/save/brief. Internal-only, feeds the preference engine. An event log — expect it to grow fast; a candidate for a separate analytics store if volume gets high.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| brand_id | FK → brands |
| talent_id | FK → talents NULL |
| action_type | ENUM(view, save, brief_sent, profile_open) |
| context | JSON NULL |
| created_at | timestamp |

---

## 5. Campaigns

### `campaigns`
Campaigns/shoots the brand runs on Fama — the "Create a Campaign / Create a shoot" feature. A brand's public-facing project, distinct from a deal: one campaign can group many deals with different talents. Shown on the brand profile as "Campaigns on FAMA."

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| brand_id | FK → brands |
| title | VARCHAR |
| slug | VARCHAR, UNIQUE |
| type | ENUM(campaign, shoot) |
| description | TEXT NULL |
| cover_image_url | VARCHAR NULL |
| status | ENUM(draft, open, in_progress, completed, cancelled) |
| budget_min | DECIMAL(10,2) NULL |
| budget_max | DECIMAL(10,2) NULL |
| currency | CHAR(3) |
| location_city | VARCHAR NULL |
| location_country | VARCHAR NULL |
| start_date | DATE NULL |
| end_date | DATE NULL |
| is_public | BOOLEAN (listed/discoverable vs. private) |
| positions_count | UNSIGNED INT (how many talents needed) |
| created_at / updated_at / deleted_at | timestamps + soft delete |

### `campaign_talent_types` (optional — roles a campaign seeks)
Names the professions a campaign needs (e.g. "1 model + 1 photographer"). Skip if campaigns are free-form.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| campaign_id | FK → campaigns |
| talent_type_id | FK → talent_types |
| quantity | UNSIGNED SMALLINT |
| — | UNIQUE(campaign_id, talent_type_id) |

### `campaign_media` (optional — the campaign's gallery)
Lets a campaign show imagery, and lets completed campaigns become showcases on the brand profile.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| campaign_id | FK → campaigns |
| media_type | ENUM(image, video, embed) |
| media_url | VARCHAR |
| thumbnail_url | VARCHAR |
| caption | VARCHAR NULL |
| position | UNSIGNED INT |
| created_at / updated_at | timestamps |

---

## 6. Platform & admin

### `users` (admins)
The admin/staff login table. Talents and brands authenticate against their own tables; this is only the people who run the platform.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| name | VARCHAR |
| email | VARCHAR, UNIQUE |
| email_verified_at | TIMESTAMP NULL |
| password | VARCHAR |
| role | ENUM(talent, brand, admin) — or use a roles table |
| phone | VARCHAR NULL |
| avatar_url | VARCHAR NULL |
| locale | ENUM(en, ar) NULL (bilingual support) |
| last_login_at | TIMESTAMP NULL |
| is_active | BOOLEAN |
| remember_token | VARCHAR NULL |
| created_at / updated_at / deleted_at | timestamps + soft delete |

### `activity_log` (`spatie/laravel-activitylog`)
Audit trail of who changed what — admin edits to flows, block-catalog changes, profile moderation, deal-step overrides. Given how admin-controlled the platform is, this matters.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| log_name | VARCHAR |
| description | TEXT |
| subject_type / subject_id | morph (the thing changed) |
| causer_type / causer_id | morph (who changed it) |
| properties | JSON (old/new values) |
| created_at / updated_at | timestamps |

### `settings` (platform-wide config — optional but useful)
Key-value store for admin-tunable global settings (default currency, default deal flow, feature flags) that aren't worth a dedicated table.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| key | VARCHAR, UNIQUE |
| value | JSON |
| created_at / updated_at | timestamps |

---

## Relationship quick-map

- **talents** ⇄ **talent_types** via `talent_talent_type` (M:N; `is_primary`, `position`).
- **talents** → many `profile_blocks`; each `profile_blocks` → one `block_types`.
- **block_types** → many `block_type_category` (when `availability = by_category`).
- **talents** → many of every content table (`portfolio_items`, `brand_collabs`, `reviews`, `services`, `look_types`, `digitals`, `showreels`, `equipment`, `case_studies`, `software_stack`, `agency_affiliations`, `press_features`); 1:1 `comp_cards`.
- **deal_flows** → many `deal_flow_steps`.
- **deals** belongs to `brands`, `talents`, `deal_flows` (+ optional `service`, future `campaign`); has many `deal_steps` and `deal_messages`; `current_step_id` → `deal_steps`.
- **deal_steps** snapshot from `deal_flow_steps` (`flow_step_id`).
- **deal_enquiries** → optional `converted_deal_id` → `deals`.
- **brands** → 1:1 `brand_aesthetics`, `brand_creative_needs`, `brand_credibility`; many `brand_images`, `brand_reviews`, `brand_social_handles`, `brand_signals`, `campaigns`.
- **campaigns** → many `campaign_talent_types`, `campaign_media`; group many `deals`.
- **users**, **activity_log**, **settings** — platform/admin plane.
