# Fama — Talent Spec (Pages · Workflows · Lifecycles)

> Canonical description of the talent side of Fama. Transcribed and lightly cleaned (typos fixed, formatting normalized) from the original design docs — **no logic changed**. Also documents the shared **deal engine**, which the talent deal room consumes. Pair with `schema-master.md` and `brand-spec.md`.

---

## PAGES

### Public pages (no login wall)

**Talent profile page — `fama.com/{slug}`**
The core product. Reads `talents` for the header (display name, headline, hero/avatar, availability badge, location, booking CTA), then renders blocks from `profile_blocks` in `position` order, skipping `is_visible = false`. The headline combines the talent's professions from `talent_talent_type`, with `is_primary` leading. Block bodies pull from their tables: gallery (`portfolio_items`), brand collabs, reviews, services rate card, and type-specific sections gated by the `block_types` catalog (a block's `availability` matched against the talent's categories/types) — so a model-photographer shows comp card + looks + digitals **and** showreel + equipment + software. Each view bumps `view_count`. Blocks resolve through `block_type_id → block_types` for rendering; grandfathered blocks (deactivated but kept) still render here.

**Project detail page — `fama.com/{slug}/work/{project}`**
One `projects` record expanded: title, client, role, cover image, full body, results metrics. The one block rich enough to warrant its own URL.

**Deal initiation (booking CTA) — now starts a deal**
The booking CTA no longer just sends an enquiry. Driven by `booking_type`/`booking_value`, it now creates a `deals` row: the brand picks the service and the applicable `deal_flow`, which snapshots into `deal_steps` and opens the deal room. Checks `availability_status` first. (This is the old "booking/enquiry page," upgraded.)

**Review submission page**
Public form a past client uses → writes to `reviews` with `is_approved = false` (pending). Captures reviewer name/role/company, rating, body, project type.

**Discovery / search page** *(needs schema additions)*
Browse/filter talent by type (join through the pivot), availability, location, or equipment. Not buildable without filterable/indexed fields — flagged for schema additions.

### Authenticated — talent dashboard

**Dashboard home**
Status overview: draft vs live (`is_published`), `view_count`, pending reviews count (`is_approved = false`), and active deals + whose turn it is (replacing the old "recent enquiries").

**Profile editor**
Live, reorderable view of `profile_blocks` — add, fill, drag (`position`), toggle `is_visible`. Edits core `talents` fields (name, bio, hero, slug). The block picker is driven by the `block_types` catalog: it lists only blocks that are `is_active` and that the talent is eligible for (universal, or gated to one of their categories/types), and omits non-repeatable blocks already on the profile.

**Professions manager**
Add/remove talent types, mark one `is_primary`, order them (`position`). Adding a type merges its `default_blocks` and seeds any missing blocks.

**Block content editors** *(surfaced by the talent's types/categories)*
Gallery (`portfolio_items`), Comp card (`comp_cards`), Looks (`look_types`), Digitals (`digitals`), Showreel (`showreels`), Equipment (`equipment`), Projects (`projects`), Software (`software_stack`), Brand collabs / Press list management. Which editor opens is informed by `block_types.content_source` (inline JSON vs. linked child table).

**Services / rate card manager**
Create, price, toggle services (`is_active`); set price, currency, `price_unit`, duration.

**Availability & travel settings**
Toggle `availability_status`, plus `willing_to_travel` / `travel_regions` and `rate_tier`.

**Reviews manager**
Moderation queue: pending (`is_approved = false`) → approve/reject; view approved.

**Affiliations manager**
`agency_affiliations` — add agencies, set `representation_type` and `region`, flip `is_current`.

**Deal room (talent view)**
The single deal page. Stepper showing `deal_steps` progress, an action panel for the current step rendered by `step_type` (send quote, accept, upload, sign, pay), and the `deal_messages` chat-like timeline. UI adapts to whether it's the talent's turn.

**Deals inbox (talent view)**
List of the talent's deals with status, current step, and whose turn (`awaiting_talent` highlighted). Filter by status.

**Account / settings**
Slug, publish/unpublish (`is_published`), account prefs.

### Authenticated — brand dashboard *(brand-side pages that touch the shared deal room)*

**Brand dashboard home** — active deals overview, whose turn, recent talent viewed.
**Deals inbox (brand view)** — the brand's deals with status and current step (`awaiting_brand` highlighted).
**Deal room (brand view)** — same deal page, brand-side: submit brief, review/accept quotes, pay, sign — driven by the current step's `actor`/`step_type`.

---

## WORKFLOW

### Admin / system workflows

1. **Build a deal flow** *(the configurable "chat steps")* — Admin creates a `deal_flow` → adds ordered `deal_flow_steps`, each with an `actor` (brand/talent/both/admin/system), a `step_type` (form, approval, upload, payment, contract, message, schedule, info), `is_required`/`is_skippable`, and per-step `settings` → marks one default or scopes it by `applies_to` category → activates. Ongoing and editable; edits apply to **new deals only** (steps snapshot at creation).
2. **Profile moderation** — review profiles → suspend/unpublish (`is_published`) → soft-delete (`deleted_at`).
3. **Profession template management** — edit a `talent_types.default_blocks` → changes what new talents of that type get seeded → add professions without code.
4. **Review moderation queue** — collect all `is_approved = false` → approve/reject in batch.
5. **Media processing (background)** — on upload → queue job → generate thumbnails, validate, write URLs back to `portfolio_items`/`digitals`.
6. **Admin deal intervention** — open any deal → override a stuck step's status, act as the admin actor where a step requires it, nudge/reassign/cancel.

### Brand-side workflows

7. **Discover & view a talent** — land on `fama.com/{slug}` → read profile → check `availability_status`.
8. **Initiate a deal** *(replaces the old fire-and-forget enquiry)* — click the booking CTA → pick the service and applicable `deal_flow` → `deals` row created → flow steps snapshot into `deal_steps` → first step activates, status set to that step's actor → deal room opens.
9. **Work the deal (brand turn)** — when a step's actor is brand/both → perform the action in the deal room (submit brief, review/accept a quote, sign, pay) → step marked completed, data saved to its `payload`, a `system_event` posted → deal advances to the next actor.

### Talent-side workflows

10. **Onboarding / profile creation** — sign up → select one or more talent types (mark one `is_primary`) → system merges `default_blocks` of all selected types and dedupes → seeds `profile_blocks` → fill core `talents` fields → fill type-specific blocks → set availability + booking method → publish.
11. **Manage professions** — add a type (merges its `default_blocks`, seeds missing blocks) → remove → reorder (`position`) → change `is_primary`. `UNIQUE(talent_id, talent_type_id)` blocks duplicates.
12. **Build the profile (blocks)** — add block → pick `block_type` → fill content → set `position` → toggle `is_visible`. Repeated: add, fill, drag, hide, delete.
13. **Add portfolio work** — open gallery → upload → thumbnail generated → attach credits/tags → order. Same shape for digitals (own section, `shot_type`).
14. **Model setup** *(any model-category type)* — fill the single `comp_cards` record → add `look_types` → upload `digitals`.
15. **Crew/creative setup** *(any crew/creative-category type)* — add `showreels` → list `equipment` by category → write `projects` → add `software_stack`. A model-photographer runs 14 **and** 15.
16. **Services / rate card** — create service → set price, currency, `price_unit` → toggle `is_active` → edit/remove.
17. **Update availability** — flip `availability_status` (available → booked → unavailable); drives the hero block and CTA.
18. **Work the deal (talent turn)** — when a step's actor is talent/both → perform the action in the deal room (send quote, accept brief, upload deliverables, sign) → step completed, payload saved, `system_event` posted → deal advances.
19. **Reviews moderation** — pending (`is_approved = false`) → approve/reject.
20. **Affiliations & press** — add `agency_affiliation` (`is_current = true`) → mark past when representation ends → add `press_features`.

### The deal loop (how a single deal walks both parties through the admin-defined steps)

- **Initiate** (brand or talent) → `deals` row + `deal_steps` snapshot → first step active.
- **Step turn** → the step's actor acts in the deal room → step completed, payload saved, `system_event` appended to `deal_messages` → next step activates → status flips between `awaiting_brand` ⇄ `awaiting_talent` (⇄ `awaiting_admin`).
- **Branches** → a step can be **rejected** (loops back to an earlier step — e.g. brand rejects quote, talent re-quotes) or **skipped** (if `is_skippable`).
- **Free messaging** runs alongside — `deal_messages` of `type = message` interleave with the `system_event` records, so it reads like a chat.
- **Complete** → last step done → `status = completed` → triggers the review workflow (#19), and any payment/contract steps have settled through their layers.
- **Terminate** → `cancelled` / `declined` / `expired`, reachable from any active state.

---

## LIFECYCLES

**Deal flow (template) lifecycle**
`draft → active → (optionally marked default) → archived`. Built and edited by admin in the flow builder. Because each deal snapshots its steps at creation, editing or archiving a flow only affects deals created **after** the change — in-flight deals are untouched. Archiving stops a flow being offered for new deals without disturbing existing ones.

**Deal lifecycle** *(replaces the old booking lifecycle — enquiries are now stateful)*
`draft/initiated → awaiting_brand ⇄ awaiting_talent (⇄ awaiting_admin)` as steps alternate by actor `→ completed`. The status always mirrors whose turn it is (the current step's actor). Terminal branches reachable from any active state: `cancelled` (either party or admin), `declined` (refused upfront), `expired` (no action in time). Soft-delete (`deleted_at`) removes without losing history. Completion is the trigger that opens the review lifecycle.

**Deal step lifecycle**
`pending → active → awaiting_action → completed`. Side exits: `skipped` (if `is_skippable`) and `rejected` (kicks the deal back to an earlier step for a redo — e.g. quote rejected → talent re-quotes). Only one step is active/awaiting_action at a time; `position` sets the order. Each completion stamps `completed_by`/`completed_at` and saves its `payload`.

**Deal message lifecycle**
`sent → read` (`read_at` stamped). `message` types are user content; `system_event` types are immutable transition records that power the timeline. Nothing in the thread is editable after posting — it's the audit trail of the deal.

**Talent profile lifecycle**
`created → draft` (blocks seeded from the merged `default_blocks` of all linked types) `→ live` (`is_published = true`, `published_at` stamped) → can return to unpublished, be suspended/archived, soft-deleted (`deleted_at`), or purged.

**Availability lifecycle**
`available → booked → unavailable` and back. Fast, frequent; drives the hero block and whether the booking CTA / deal initiation is open.

**Block lifecycle**
`seeded (from profession defaults) or added → edited → visible ⇄ hidden (is_visible) → reordered (position, repeatedly) → removed`.

**Review lifecycle**
`submitted (is_approved = false) → pending/moderation → approved (public) or rejected/hidden`. Now naturally triggered when a deal hits `completed`.

**Service lifecycle**
`created → active (is_active = true) → paused (is_active = false) ⇄ active → edited → removed`.

**Profession affiliation lifecycle** (`talent_talent_type`)
`attached (one marked is_primary) → reordered (position) → primary reassigned → detached`. Attaching merges new default blocks; UNIQUE prevents duplicates.

**Agency affiliation lifecycle**
`added (is_current = true) → current → past (is_current = false) → removed`.

**Media / portfolio lifecycle**
`uploaded → processed (thumbnail generated) → ordered (position) → visible → archived/deleted`.

**Onboarding lifecycle (account level)**
`sign up → select talent type(s), mark primary → blocks auto-seeded (merged) → fill core fields → fill type-specific blocks → set availability + booking method → publish`.
