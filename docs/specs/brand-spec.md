# Fama — Brand Spec (Pages · Workflows · Lifecycles)

> Canonical description of the brand side of Fama. Transcribed and lightly cleaned (typos fixed, sections reordered to Pages → Workflows → Lifecycles for parallelism with `talent-spec.md`) — **no logic changed**. One correction: the source repeated the "Edit brand profile" text under "Tune creative needs"; it's written here as its intended meaning (editing `brand_creative_needs`). Pair with `schema-master.md` and `talent-spec.md`.

---

## PAGES

### Public / talent-facing pages

**Brand profile page — `fama.com/brands/{slug}`**
Header from `brands` (name, logo, cover, description, industry, stage, location), credibility block (`brand_credibility`), talent ratings (`brand_reviews`), Campaigns on FAMA (public campaigns), social handles (`brand_social_handles`).

**Campaign detail page — `fama.com/brands/{slug}/campaigns/{campaign-slug}`**
One campaign expanded: title, description, cover, budget, location, dates, roles sought (`campaign_talent_types`), gallery (`campaign_media`).

### Authenticated — brand dashboard

**Brand dashboard home**
Overview: profile completion status (`is_complete`), active deals + whose turn, recent campaigns, and the discovery feed entry point.

**Brand profile editor**
Edits `brands` core fields, `brand_aesthetics`, `brand_images`, `brand_social_handles`.

**Creative needs / preferences editor**
Edits `brand_creative_needs` — talent types, project types, frequency, budget tier.

**Campaigns manager**
List/create/edit campaigns; set status, budget, location, dates, `is_public`, attach `campaign_talent_types` and `campaign_media`.

**Single campaign workspace**
One campaign's details plus the deals running under it (`deals.campaign_id`).

**Discovery feed** *(the onboarding payoff — needs the matching layer)*
Personalised talent feed filtered by `brand_creative_needs`, `geographic_reach`, `brand_aesthetics`; writes browse/save events to `brand_signals`.

**Deals inbox (brand view)**
The brand's deals with status and current step.

**Deal room (brand view)**
Per-deal thread: submit brief, review/accept quotes, pay, sign.

**Brand reviews received**
View `brand_reviews` from talents — the three sub-ratings and feedback.

**Account / settings**
Settings-stage fields (`company_size`, `website`, `founded_year`), social handles, slug, publish toggle (`is_published`).

### Onboarding (the 6-step wizard)

**Brand onboarding wizard**
- Step 1 — identity + persona → `brands`.
- Step 2 — location/reach → `brands`.
- Step 3 — creative needs → `brand_creative_needs`.
- Step 4 — aesthetic → `brand_aesthetics` + `brand_images`.
- Step 5 — budget → `brand_creative_needs.budget_tier`.
- Step 6 — first discovery feed. Flips `is_complete`.

---

## WORKFLOW

### Onboarding & profile

1. **Brand registration & onboarding** — Register (email/password) → walk the 6-step wizard: identity → `brands`, location/reach → `brands`, creative needs → `brand_creative_needs`, aesthetic → `brand_aesthetics` + `brand_images`, budget → `brand_creative_needs.budget_tier`, first feed → payoff. Completing flips `is_complete = true`, then publish (`is_published = true`).
2. **Edit brand profile** — update `brands` core fields, `brand_aesthetics` (mood tags, references), `brand_images` (upload/reorder/replace the 2–3), and `brand_social_handles`. The settings-stage fields (`company_size`, `website`, `founded_year`) get filled here later, not at onboarding.
3. **Tune creative needs** — update `brand_creative_needs`: talent types, project types, project frequency, and `budget_tier`, as the brand's hiring changes.

### Discovery & engagement

4. **Browse the discovery feed** — feed filtered by `brand_creative_needs`, `geographic_reach`, and `brand_aesthetics`. Every view/save/brief writes a row to `brand_signals`, which continuously enriches the preference profile.
5. **Initiate a deal** — from a talent profile → start a deal → becomes a `deals` row owned by the brand → enters the deal step loop. (Or a public visitor's `deal_enquiry` converts into the brand's first deal once they finish onboarding.)

### Campaigns

6. **Create a campaign / shoot** — create a campaign (draft) → set type, description, cover, budget range, location, dates → attach `campaign_talent_types` (roles sought) and `campaign_media` → set `is_public` → open it.
7. **Run a campaign** — open campaign → talents booked under it via deals (`deals.campaign_id`) → campaign moves `draft → open → in_progress → completed`. A completed campaign becomes a showcase on the brand profile; can be cancelled from any active state.

### Trust & reputation

8. **Accrue credibility** — on deal completion → `brand_credibility.completed_projects_count` increments; response metrics (`response_rate_pct`, `avg_response_time_hours`) recalculate as the brand replies to enquiries; `brief_quality_score` updates internally. Automatic, event-driven — the brand doesn't act here.
9. **Receive talent reviews** — when a deal completes → the talent rates the brand on three axes (communication, fairness, creative respect) → review enters `brand_reviews` as `is_approved = false` → the brand can view it once approved, but cannot edit it.

### Admin workflows

10. **Brand moderation** — review `brands` → verify (`is_verified`), suspend/unpublish (`is_published`), soft-delete (`deleted_at`).
11. **Brand review moderation** — queue of `brand_reviews` where `is_approved = false` → approve/reject.
12. **Campaigns oversight** — view all campaigns across brands, filter by status, intervene/cancel.

---

## LIFECYCLES

**Brand lifecycle**
`registered` (email/password created via the Contact-to-deal flow or direct signup) `→ onboarding` (`is_complete = false`, walking the 6 steps) `→ complete` (`is_complete = true`, the deal-flow gate opens) `→ published` (`is_published = true`, profile visible to talents) → can go unpublished, `verified` (`is_verified` flipped by admin, a one-way trust upgrade), suspended/deactivated (`is_active = false`), soft-deleted (`deleted_at`), or purged. **The two gates that matter:** `is_complete` controls whether the brand can transact; `is_published` controls whether talents can see it.

**Brand aesthetics lifecycle**
`empty → set at onboarding (Step 4) → refined` (edited in the profile editor, or implicitly enriched from browsing) `→ edited freely thereafter`. One-to-one with the brand, so it's created once and updated in place — no terminal state of its own beyond the brand being deleted.

**Brand images lifecycle**
`uploaded → processed (thumbnail generated) → ordered (position) → displayed → replaced/removed`. Capped at the 2–3 the brand provides; swapping one out is a delete-and-re-add.

**Brand creative needs lifecycle**
`set at onboarding (Steps 3 + 5) → active (driving the discovery feed) → adjusted as the brand's hiring changes`. Like aesthetics, one-to-one and update-in-place; `budget_tier` shifts as the brand grows.

**Brand credibility lifecycle**
`initialized at zero → accrues over time`. Counters move only on real events: `completed_projects_count` increments when a deal completes; `response_rate_pct` / `avg_response_time_hours` recalculate as the brand replies to enquiries; `brief_quality_score` updates internally. It never resets — a monotonic trust record (response rate can dip, but the project count only climbs).

**Brand review lifecycle**
`submitted by talent (is_approved = false) → pending/moderation → approved (public) or rejected/hidden`. Triggered when a deal hits `completed` — the moment a talent can rate the brand on the three axes. Mirrors the talent-side review lifecycle exactly.

**Brand social handle lifecycle**
`added (settings-stage) → displayed → edited → removed`. A simple list item, reorderable by `position`; no states beyond present/absent.

**Brand signal lifecycle**
`emitted → stored (immutable)`. Each view/save/brief is a one-time append-only event — never edited or transitioned, it just accumulates and feeds the preference engine. Effectively write-once; the only "lifecycle" is eventual aging-out/archival if moved to an analytics store.

**Campaign lifecycle**
`draft → open (published, accepting/seeking talent) → in_progress (deals running under it) → completed` → can also be `cancelled` from any active state. Soft-delete (`deleted_at`) for removal. Independently, `is_public` toggles listed ⇄ private without changing the status. A completed campaign becomes a showcase on the brand profile.

**Campaign media lifecycle**
`uploaded → processed → ordered (position) → displayed → removed`. Same shape as brand images, scoped to the campaign.

### How they connect
The **brand lifecycle is the spine** — `is_complete` gates transacting, `is_published` gates visibility. The **campaign lifecycle runs on top** (a brand spins up many campaigns over its life). Both the **credibility** and **review** lifecycles are consequences of deals completing — they don't advance on their own; they advance when the deal lifecycle reaches `completed`. Aesthetics, creative needs, images, and social handles are all **update-in-place satellites** of the brand with no terminal state of their own; **signals are append-only**. The one entity with no real lifecycle is `brand_signals` — it's logged, not transitioned.
