# Fama — Database Schema (Canonical)

> Single source of truth for the Fama data model — **45 tables** (35 pre-feed + the 10 feed tables,
> §7). Transcribed and lightly cleaned (typos fixed, ordering and formatting normalized) from the original design docs. Tables are grouped by domain for readability; grouping is presentational only.
>
> **Implementation note:** per `docs/decisions.md`, all **uploaded** files go through `spatie/laravel-medialibrary` (collections + conversions). The `*_url` / `thumbnail_url` columns below for *uploaded* assets are replaced at implementation time by media-library accessors; plain URL columns are kept only for **external** links/embeds (YouTube/Vimeo, social, brand-collab, press). The schema is documented here as originally designed.
>
> **Removed features (`docs/decisions.md` ADR-K/L/M):** the `services` table, `agency_affiliations`/`press_features` tables, and the `talents` columns `availability_status`, `rate_tier`, `willing_to_travel`, `travel_regions` were removed entirely, along with `contracts.service_id` and `contract_enquiries.service_id`. Those tables/columns are struck below and no longer exist in the schema.

---

## 1. Talent core & block system

### `talents`
The profile itself — the "living creative passport." Holds one-per-profile identity data (name, bio, hero image, public slug shown as **Username**) and the singular settings that only ever have one value per person (booking method + the indicative **Pricing rate**). Anything that is "one talent = one value" lives here rather than in a child table. A talent's skill(s) are linked through `talent_talent_type`, and the merged `default_blocks` of those skills decide the default layout. Soft deletes let you unpublish/remove without losing history. *(The availability, rate-tier, and travel settings were removed — ADR-L. The Pricing rate `rate_*` columns were added — ADR-N.)*

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
| ~~hero_image_url~~ | ~~VARCHAR~~ — cover/hero removed with the IG-style header (ADR-O); was a media accessor |
| avatar_url | VARCHAR — media accessor (`avatar` collection) |
| ~~availability_status~~ | ~~ENUM(available, booked, unavailable)~~ — removed (ADR-L) |
| base_city | VARCHAR |
| base_country | VARCHAR |
| ~~rate_tier~~ | ~~ENUM(emerging, established, premium, elite) NULL~~ — removed (ADR-L) |
| ~~willing_to_travel~~ | ~~BOOLEAN~~ — removed (ADR-L) |
| ~~travel_regions~~ | ~~JSON NULL~~ — removed (ADR-L) |
| booking_type | ENUM(email, calendar, form, external) |
| booking_value | VARCHAR |
| rate_unit | ENUM(project, day, hour) NULL — Pricing rate (ADR-N) |
| rate_amount | DECIMAL(10,2) NULL — Pricing rate (ADR-N) |
| rate_currency | CHAR(3) NULL — ISO code; Pricing rate (ADR-N) |
| is_published | BOOLEAN |
| published_at | TIMESTAMP NULL |
| view_count | UNSIGNED INT |
| followers_count | UNSIGNED INT, default 0 — feed counter (ADR-F6) |
| following_count | UNSIGNED INT, default 0 — feed counter (ADR-F6) |
| posts_count | UNSIGNED INT, default 0 — feed counter (ADR-F6) |
| last_posted_at | TIMESTAMP NULL, indexed — denormalized last-publish time (Prompt 5-follow); maintained by CounterService on the publish boundary + reconciled nightly; powers the dashboard "quiet since" prune signal and the least-active sort |
| meta | JSON NULL |
| created_at / updated_at / deleted_at | timestamps + soft delete |

### `talent_types` — the **Skills catalog** (ADR-N)
`talent_types` is the **Skills catalog**: the six seeded skills, named as **disciplines/activities** — Modeling, Photography, Cinematography, Creative Direction, Styling, Graphic Design (slugs `modeling` / `photography` / `cinematography` / `creative-direction` / `styling` / `graphic-design`) — not person-nouns (ADR-S; IDs unchanged, so every FK is intact). "Skills" is the product term everywhere in the UI/routes; the physical table keeps the `talent_types` name (renaming it would cascade into brand creative-needs, projects, and API lookups — a deliberate future migration, ADR-N). Its real job is `default_blocks`: when a new talent picks "Photography," this is where the system reads which blocks to preload and in what order. Keeping it as a table (not hardcoded) means you can add a skill or change a default layout without touching code or migrations.

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
Makes talent ↔ skill many-to-many, so one talent can have more than one skill (e.g. Photography + Creative Direction, or Modeling + Photography). `is_primary` marks the skill that leads the profile and drives the headline; `position` orders the rest. On profile creation the system merges the `default_blocks` of every linked skill and dedupes before seeding `profile_blocks`, so a multi-skill talent gets no duplicate blocks. `UNIQUE(talent_id, talent_type_id)` stops the same skill being attached twice.

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
Each row is a block the platform offers. `availability` is the key field — whether every talent can have it, or only certain categories/types. **Governance is split (ADR-T):** the admin **Block Catalog Manager** (`/admin/blocks`, permission `manage-blocks`) owns this table — existence, eligibility (`availability` + the two gate tables), and config (`is_active`, `is_repeatable`, `default_layout`, `content_source`, `settings_schema` — validated JSON). The **Skills Template Manager** (`/admin/skills`) owns only `talent_types.default_blocks` (ordered preselection) and may only pick catalog-eligible blocks. `key` and `content_source` are immutable once any `profile_blocks` reference the type; deactivating/narrowing eligibility grandfathers existing placements (they keep rendering; the type stops being offered).

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
The heart of the "malleable, reorderable" system. Every block a talent shows is a row here, with `position` for ordering, `is_visible` for show/hide, and `block_type_id` to say what it is. This table is layout and arrangement, deliberately separated from the actual content: simple blocks store data inline in `content` (JSON); rich blocks point to their own tables. Blocks are **skill-scoped** (ADR-Q): `talent_type_id` NULL = a profile-level / universal block (rendered above the tabs); NOT NULL = the block lives in that skill's tab. `position` is ordered **within a scope** (per `talent_id, talent_type_id`).

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| talent_id | FK → talents |
| block_type_id | FK → block_types |
| talent_type_id | FK → talent_types NULL, nullOnDelete — the skill's tab; NULL = profile-level (ADR-Q); index `(talent_id, talent_type_id, position)` |
| title | VARCHAR |
| position | UNSIGNED INT (ordered within the scope) |
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

### ~~`services`~~ — removed (ADR-K)
The per-talent rate card was removed entirely. Pricing is now the single indicative **Pricing rate** on `talents` (`rate_unit`/`rate_amount`/`rate_currency`, ADR-N), and a contract's amount comes from the flow's form/quote step — not a service.

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

### `projects`
Long-form project write-ups for creatives. Each is a substantial content object (summary, full body, cover image, measurable results). The `results` JSON holds flexible metrics per case without needing fixed columns. Projects are **skill-scoped** (ADR-Q): `talent_type_id` says which skill's tab a project belongs to.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| talent_id | FK → talents |
| talent_type_id | FK → talent_types NULL, nullOnDelete — the project's skill/tab (ADR-Q); indexed |
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

### ~~`agency_affiliations`~~ — removed (ADR-M)
The agency-representation satellite was removed entirely.

### ~~`press_features`~~ — removed (ADR-M)
The press-mentions satellite was removed entirely.

---

## 3. Contract engine

### `contract_flows`
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

### `contract_flow_steps`
One row per step in a flow. `actor` says who must act, `step_type` says what kind of interaction it is, and `settings` holds per-step config (required fields, payment %, contract template id). This is the table the admin flow-builder writes to.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| contract_flow_id | FK → contract_flows |
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

### `contracts` (the engagement instance)
One row per brand ↔ talent contract. `current_step_id` points at the active step; `status` mirrors whose turn it is so the inbox can filter. The brief, agreed amount, and shoot dates live here as the contract's headline data.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| reference | VARCHAR, UNIQUE (human code, e.g. FAMA-2026-0001) |
| brand_id | FK → brands |
| talent_id | FK → talents |
| ~~service_id~~ | ~~FK → services NULL~~ — removed (ADR-K); amount comes from the flow's form/quote step |
| contract_flow_id | FK → contract_flows (the flow it was seeded from) |
| current_step_id | FK → contract_steps NULL |
| status | ENUM(draft, awaiting_brand, awaiting_talent, awaiting_admin, completed, cancelled, declined, expired) |
| title | VARCHAR |
| brief | TEXT NULL |
| agreed_amount | DECIMAL(10,2) NULL |
| currency | CHAR(3) |
| start_date | DATE NULL |
| end_date | DATE NULL |
| initiated_by | ENUM(brand, talent) |
| created_at / updated_at / deleted_at | timestamps + soft delete |

> **Open item:** the brand workflows reference `contracts.brand_project_id` (a contract running under a project). This FK is **not yet present** in the table above — tracked as an open decision. Add `brand_project_id — FK → projects NULL` when the project↔contract link is finalized.

### `contract_steps` (per-contract snapshot of the flow steps — the progress tracker)
When a contract starts, the flow's steps are snapshotted here so editing the template later doesn't break in-flight contracts. Each row tracks its own status and stores what was captured (`payload` = the quote amount, brief answers, uploaded-file references).

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| contract_id | FK → contracts |
| flow_step_id | FK → contract_flow_steps NULL (origin reference) |
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

### `contract_messages` (the chat-like thread)
What makes the contract room look like a chat. Free-text messages and system events interleaved chronologically, optionally tied to a step. `type` lets "Talent accepted the quote" render as a system event alongside real messages, so the timeline reads naturally.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| contract_id | FK → contracts |
| contract_step_id | FK → contract_steps NULL |
| sender_type | VARCHAR NULL (morph: talents / brands / users) — null for system |
| sender_id | BIGINT UNSIGNED NULL (morph) — null for system |
| sender_role | ENUM(brand, talent, admin, system) |
| type | ENUM(message, system_event, action_summary) |
| body | TEXT NULL |
| attachments | JSON NULL |
| read_at | TIMESTAMP NULL |
| created_at / updated_at | timestamps |

### `contract_enquiries` (no-login capture from the Contact button)
The pre-auth holding table. When a visitor presses Contact on a public talent profile, the enquiry lands here before any brand account exists. Once the visitor authenticates and completes their brand profile, the enquiry converts into a `contracts` row and `converted_contract_id` is set.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| talent_id | FK → talents |
| ~~service_id~~ | ~~FK → services NULL~~ — removed (ADR-K) |
| contact_name | VARCHAR |
| contact_email | VARCHAR |
| contact_company | VARCHAR NULL |
| brief | TEXT |
| status | ENUM(new, converted, declined, expired) |
| converted_contract_id | FK → contracts NULL |
| created_at / updated_at | timestamps |

---

## 4. Brand core & satellites

### `brands`
The brand-side identity — the foundation everything sits on. Holds the one-per-brand public-facing identity plus onboarding-collected basics. Admin-only fields (company size, website, founded year) are deferred to settings per the onboarding philosophy, so they're nullable. `is_complete` gates the Contact-to-contract flow.

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
| is_complete | BOOLEAN (contract-flow gate) |
| is_verified | BOOLEAN |
| is_published | BOOLEAN |
| view_count | UNSIGNED INT |
| following_count | UNSIGNED INT, default 0 — feed counter (ADR-F6; a brand follows talents. `followers_count` is deliberately omitted until `feed.allow_following_brands` flips) |
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
| contract_id | FK → contracts NULL (the completed project it's tied to) |
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

## 5. Projects

### `brand_projects`
Projects/shoots the brand runs on Fama — the "Create a Project / Create a shoot" feature. A brand's public-facing project, distinct from a contract: one project can group many contracts with different talents. Shown on the brand profile as "Projects on FAMA." **As implemented (Phase 2, ADR-F):** one project seeks **one role** (`talent_type_id` — the `brand_project_talent_types` pivot and `positions_count` were dropped), and `budget_is_public` (default **false**) withholds a private budget from every non-owning viewer in the payload.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| brand_id | FK → brands |
| title | VARCHAR |
| slug | VARCHAR, UNIQUE |
| type | ENUM(campaign, shoot) |
| description | JSON NULL (translatable) |
| cover_image_url | VARCHAR NULL — media accessor (medialibrary) |
| status | ENUM(draft, open, in_progress, completed, cancelled) |
| budget_min | DECIMAL(10,2) NULL |
| budget_max | DECIMAL(10,2) NULL |
| currency | CHAR(3) |
| location_city | VARCHAR NULL |
| location_country | VARCHAR NULL |
| start_date | DATE NULL |
| end_date | DATE NULL |
| is_public | BOOLEAN (listed/discoverable vs. private) |
| talent_type_id | FK → talent_types NULL, nullOnDelete — the ONE role sought (ADR-F) |
| budget_is_public | BOOLEAN, default false |
| created_at / updated_at / deleted_at | timestamps + soft delete |

### ~~`brand_project_talent_types`~~ — never built (ADR-F)
One project = one role = one position via `brand_projects.talent_type_id`; the many-roles pivot (and `positions_count`) was dropped from the design before implementation.

### `brand_project_media` (optional — the project's gallery)
Lets a project show imagery, and lets completed projects become showcases on the brand profile.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| brand_project_id | FK → projects |
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
Audit trail of who changed what — admin edits to flows, block-catalog changes, profile moderation, contract-step overrides. Given how admin-controlled the platform is, this matters.

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
Key-value store for admin-tunable global settings (default currency, default contract flow, feature flags) that aren't worth a dedicated table.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| key | VARCHAR, UNIQUE |
| value | JSON |
| created_at / updated_at | timestamps |

---

## 7. Feed

> The public feed (Phase 5) — see `feed-spec.md` for behaviour and `docs/decisions.md` ADR-F1…F8
> for the rulings. All actor-side references are **polymorphic morphs** (aliased via an enforced
> morph map: `talent`, `brand`, `user`, `post`, `comment`, `project`) so brands can be enabled as
> authors later by policy alone (ADR-F1). Denormalized counters are written inside the same
> transaction as the source write, reconciled nightly by `feed:reconcile-counters` (ADR-F6).

### `posts`
A talent's published unit of content: a text `note`, a `media` set (1–10 items via the `post_media` medialibrary collection — no media columns here), or a `project_share` referencing the talent's own Project with a `shared_snapshot` fallback (ADR-F2). Author is a morph, gated to talents by `PostPolicy` (ADR-F1). `state` drives the `PostState` machine (draft/published/hidden/removed); author deletes are soft deletes (D12).

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| author_type | VARCHAR (morph — `talent`; `brand` reserved) |
| author_id | BIGINT UNSIGNED (morph) |
| post_type | ENUM(note, media, project_share) |
| body | LONGTEXT NULL (raw as typed, ≤ 2200 chars enforced in validation) |
| project_id | FK → projects NULL, nullOnDelete — the shared Project (project_share only) |
| shared_snapshot | JSON NULL — `{title, cover_media_path, shared_at}` frozen at share time (ADR-F2) |
| state | VARCHAR — PostState (draft, published, hidden, removed) |
| likes_count | UNSIGNED INT, default 0 |
| comments_count | UNSIGNED INT, default 0 |
| shares_count | UNSIGNED INT, default 0 |
| published_at | TIMESTAMP NULL |
| edited_at | TIMESTAMP NULL — set on any post-publish edit; renders the `edited` marker |
| is_pinned | BOOLEAN, default false — max 1 pinned per talent |
| created_at / updated_at / deleted_at | timestamps + soft delete |
| — | INDEX(author_type, author_id, published_at) · INDEX(state, published_at) · INDEX(project_id) |

### `post_skill` (pivot)
Skill tags on a post, referencing the `talent_types` Skills catalog. Powers skill-affinity ranking, the credits line, and `/feed?skill={slug}`.

| Column | Type / constraints |
|---|---|
| post_id | FK → posts, cascadeOnDelete |
| talent_type_id | FK → talent_types, cascadeOnDelete |
| — | PRIMARY KEY(post_id, talent_type_id) |

### `comments`
Two-level thread (ADR-F7): a top-level comment has `parent_id NULL`; a reply points at its parent. A reply **to a reply** attaches to the same parent with an auto-prefixed @mention, so depth never exceeds two. Deleted comments with replies render as tombstones (D12).

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| post_id | FK → posts, cascadeOnDelete |
| parent_id | FK → comments NULL (self), cascadeOnDelete |
| author_type | VARCHAR (morph — `talent` \| `brand`) |
| author_id | BIGINT UNSIGNED (morph) |
| body | TEXT (raw as typed) |
| likes_count | UNSIGNED INT, default 0 |
| replies_count | UNSIGNED INT, default 0 |
| state | VARCHAR — CommentState (published, hidden, removed) |
| edited_at | TIMESTAMP NULL |
| created_at / updated_at / deleted_at | timestamps + soft delete |
| — | INDEX(post_id, parent_id, created_at) |

### `likes`
Polymorphic on both sides: what was liked (`likeable` = post \| comment) and who liked it (`liker` = talent \| brand). The unique index is the idempotency guarantee — `ToggleLike` relies on it under double submission rather than read-then-write.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| likeable_type | VARCHAR (morph — `post` \| `comment`) |
| likeable_id | BIGINT UNSIGNED (morph) |
| liker_type | VARCHAR (morph — `talent` \| `brand`) |
| liker_id | BIGINT UNSIGNED (morph) |
| created_at / updated_at | timestamps |
| — | UNIQUE(likeable_type, likeable_id, liker_type, liker_id) · INDEX(likeable_type, likeable_id) · INDEX(liker_type, liker_id) |

### `follows`
Polymorphic on both sides. Allowed edges: talent → talent, brand → talent; talent → brand behind `feed.allow_following_brands` (default false). Self-follow forbidden by policy. Append-only (`created_at` only) — unfollow deletes the row.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| follower_type | VARCHAR (morph — `talent` \| `brand`) |
| follower_id | BIGINT UNSIGNED (morph) |
| followable_type | VARCHAR (morph — `talent`; `brand` behind config) |
| followable_id | BIGINT UNSIGNED (morph) |
| created_at | TIMESTAMP |
| — | UNIQUE(follower_type, follower_id, followable_type, followable_id) · INDEX(follower_type, follower_id) · INDEX(followable_type, followable_id) |

### `mentions`
One row per @mention: which body it lives in (`mentionable` = post \| comment), who is mentioned (`mentioned` = talent \| brand), and **where** — `start_offset` + `length` into the raw body (ADR-F3). The renderer resolves the current Username at read time; a body edit deletes and re-parses (offsets shift).

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| mentionable_type | VARCHAR (morph — `post` \| `comment`) |
| mentionable_id | BIGINT UNSIGNED (morph) |
| mentioned_type | VARCHAR (morph — `talent` \| `brand`) |
| mentioned_id | BIGINT UNSIGNED (morph) |
| start_offset | UNSIGNED INT — multibyte-correct character offset into the raw body |
| length | UNSIGNED INT |
| created_at / updated_at | timestamps |
| — | INDEX(mentioned_type, mentioned_id) |

### `link_previews`
One row per unique URL, deduplicated by `url_hash` (SHA-256 of the normalized URL). Provider + handle/resource are parsed from the URL string with **zero network** at write time (ADR-F4); `FetchLinkPreviewJob` enriches best-effort afterwards. `embed_html` is stored only for allowlisted providers, sanitized.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| url_hash | CHAR(64), UNIQUE |
| url | TEXT |
| canonical_url | TEXT NULL |
| provider | VARCHAR (driver key: instagram, tiktok, youtube, behance, …, generic) |
| provider_handle | VARCHAR NULL (e.g. `@amgad`) |
| provider_resource_id | VARCHAR NULL (e.g. a video/post/gallery id) |
| title | VARCHAR NULL (enrichment) |
| description | TEXT NULL (enrichment) |
| image_url | VARCHAR NULL (enrichment — external URL, not medialibrary) |
| embed_html | TEXT NULL (allowlisted providers only, sanitized) |
| status | ENUM(pending, ok, failed) |
| fetched_at | TIMESTAMP NULL |
| expires_at | TIMESTAMP NULL (ok → +30 days) |
| created_at / updated_at | timestamps |

### `linked_urls`
Joins a body (`linkable` = post \| comment) to its `link_previews` rows, positioned in appearance order. Like mentions, rebuilt wholesale on edit.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| linkable_type | VARCHAR (morph — `post` \| `comment`) |
| linkable_id | BIGINT UNSIGNED (morph) |
| link_preview_id | FK → link_previews, cascadeOnDelete |
| position | UNSIGNED INT |
| created_at / updated_at | timestamps |

### `post_reports`
Reports on posts **and** comments (`reportable` morph, despite the table name). One report per reportable per reporter (policy-enforced); `note` is **required** when reason = `other`. `feed.auto_hide_reports` open reports (default 5) auto-hides the target pending review. `resolved_by` is the acting admin.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| reportable_type | VARCHAR (morph — `post` \| `comment`) |
| reportable_id | BIGINT UNSIGNED (morph) |
| reporter_type | VARCHAR (morph — `talent` \| `brand`) |
| reporter_id | BIGINT UNSIGNED (morph) |
| reason | VARCHAR(40) — keys from config `feed.report_reasons` (round 3: spam, nudity, harassment, hate, impersonation, ip, other; widened from ENUM by 2026_07_22_000600, legacy values mapped: inappropriate→nudity, copyright→ip, misleading→spam) |
| note | TEXT NULL |
| status | ENUM(open, reviewing, actioned, dismissed) |
| resolved_by | FK → users NULL, nullOnDelete |
| resolved_at | TIMESTAMP NULL |
| created_at / updated_at | timestamps |

### `feed_seen`
Viewer × post exposure marks — damps repeats across feed sessions and feeds discovery freshness. Written lazily (batched), pruned after 60 days by `feed:prune`.

| Column | Type / constraints |
|---|---|
| id | BIGINT UNSIGNED, PK |
| viewer_type | VARCHAR (morph — `talent` \| `brand`) |
| viewer_id | BIGINT UNSIGNED (morph) |
| post_id | FK → posts, cascadeOnDelete |
| seen_at | TIMESTAMP |
| — | UNIQUE(viewer_type, viewer_id, post_id) · INDEX(viewer_type, viewer_id, seen_at) |

### Counter columns added elsewhere (ADR-F6)
`talents.followers_count` / `talents.following_count` / `talents.posts_count`, `brands.followers_count` / `brands.following_count` — documented on their tables above. All UNSIGNED INT default 0, mutated only inside the owning write's transaction, reconciled nightly. `talents.last_posted_at` (nullable TIMESTAMP) rides the same machinery (Prompt 5-follow).

---

## Relationship quick-map

- **talents** ⇄ **talent_types** via `talent_talent_type` (M:N; `is_primary`, `position`).
- **talents** → many `profile_blocks`; each `profile_blocks` → one `block_types`.
- **block_types** → many `block_type_category` (when `availability = by_category`).
- **talents** → many of every content table (`portfolio_items`, `brand_collabs`, `reviews`, `look_types`, `digitals`, `showreels`, `equipment`, `projects`, `software_stack`); 1:1 `comp_cards`. *(`services`, `agency_affiliations`, `press_features` removed — ADR-K/M.)*
- **contract_flows** → many `contract_flow_steps`.
- **contracts** belongs to `brands`, `talents`, `contract_flows` (+ future `project`); has many `contract_steps` and `contract_messages`; `current_step_id` → `contract_steps`. *(The optional `service` link was removed — ADR-K.)*
- **contract_steps** snapshot from `contract_flow_steps` (`flow_step_id`).
- **contract_enquiries** → optional `converted_contract_id` → `contracts`.
- **brands** → 1:1 `brand_aesthetics`, `brand_creative_needs`, `brand_credibility`; many `brand_images`, `brand_reviews`, `brand_social_handles`, `brand_signals`, `brand_projects`.
- **projects** → many `brand_project_talent_types`, `brand_project_media`; group many `contracts`.
- **users**, **activity_log**, **settings** — platform/admin plane.
- **posts** (morph author → talents) ⇄ **talent_types** via `post_skill`; optional `project_id` → **projects** (+ `shared_snapshot` fallback); many `comments`, morph-many `likes` / `mentions` / `linked_urls` / `post_reports`; tracked in `feed_seen`.
- **comments** belong to `posts` (+ self `parent_id`, depth ≤ 2); morph author → talents/brands; morph-many `likes` / `mentions` / `linked_urls` / `post_reports`.
- **follows** — morph follower (talents/brands) → morph followable (talents); denormalized counters on `talents`/`brands`.
- **linked_urls** join posts/comments → **link_previews** (deduped by `url_hash`).
