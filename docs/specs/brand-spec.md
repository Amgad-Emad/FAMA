# Fama — Brand Spec (Pages · Workflows · Lifecycles)

> Canonical description of the brand side of Fama. Transcribed and lightly cleaned (typos fixed, sections reordered to Pages → Workflows → Lifecycles for parallelism with `talent-spec.md`) — **no logic changed**. One correction: the source repeated the "Edit brand profile" text under "Tune creative needs"; it's written here as its intended meaning (editing `brand_creative_needs`). Pair with `schema-master.md` and `talent-spec.md`.

---

## PAGES

### Public / talent-facing pages

**Brand profile page — `fama.com/brands/{slug}`**
Header from `brands` (name, logo, cover, description, industry, stage, location), credibility block (`brand_credibility`), talent ratings (`brand_reviews`), Projects on FAMA (public projects), social handles (`brand_social_handles`).

**Project detail page — `fama.com/brands/{slug}/projects/{project-slug}`**
One project expanded: title, description, cover, budget, location, dates, roles sought (`brand_project_talent_types`), gallery (`brand_project_media`).

### Authenticated — brand dashboard

**Brand dashboard home**
Overview: profile completion status (`is_complete`), active contracts + whose turn, recent projects, and the discovery feed entry point.

**Brand profile editor**
Edits `brands` core fields, `brand_aesthetics`, `brand_images`, `brand_social_handles`.

**Creative needs / preferences editor**
Edits `brand_creative_needs` — talent types, project types, frequency, budget tier.

**Projects manager**
List/create/edit projects; set status, budget, location, dates, `is_public`, attach `brand_project_talent_types` and `brand_project_media`.

**Single project workspace**
One project's details plus the contracts running under it (`contracts.brand_project_id`).

**Discovery feed** *(the onboarding payoff — needs the matching layer)*
Personalised talent feed filtered by `brand_creative_needs`, `geographic_reach`, `brand_aesthetics`; writes browse/save events to `brand_signals`.

**Contracts inbox (brand view)**
The brand's contracts with status and current step.

**Contract room (brand view)**
Per-contract thread: submit brief, review/accept quotes, pay, sign.

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
5. **Initiate a contract** — from a talent profile → start a contract → becomes a `contracts` row owned by the brand → enters the contract step loop. (Or a public visitor's `contract_enquiry` converts into the brand's first contract once they finish onboarding.)

### Projects

6. **Create a project / shoot** — create a project (draft) → set type, description, cover, budget range, location, dates → attach `brand_project_talent_types` (roles sought) and `brand_project_media` → set `is_public` → open it.
7. **Run a project** — open project → talents booked under it via contracts (`contracts.brand_project_id`) → project moves `draft → open → in_progress → completed`. A completed project becomes a showcase on the brand profile; can be cancelled from any active state.

### Trust & reputation

8. **Accrue credibility** — on contract completion → `brand_credibility.completed_projects_count` increments; response metrics (`response_rate_pct`, `avg_response_time_hours`) recalculate as the brand replies to enquiries; `brief_quality_score` updates internally. Automatic, event-driven — the brand doesn't act here.
9. **Receive talent reviews** — when a contract completes → the talent rates the brand on three axes (communication, fairness, creative respect) → review enters `brand_reviews` as `is_approved = false` → the brand can view it once approved, but cannot edit it.

### Admin workflows

10. **Brand moderation** — review `brands` → verify (`is_verified`), suspend/unpublish (`is_published`), soft-delete (`deleted_at`).
11. **Brand review moderation** — queue of `brand_reviews` where `is_approved = false` → approve/reject.
12. **Projects oversight** — view all projects across brands, filter by status, intervene/cancel. Admins always see the budget, tagged **private** when `budget_is_public` is off.

---

## LIFECYCLES

**Brand lifecycle**
`registered` (email/password created via the Contact-to-contract flow or direct signup) `→ onboarding` (`is_complete = false`, walking the 6 steps) `→ complete` (`is_complete = true`, the contract-flow gate opens) `→ published` (`is_published = true`, profile visible to talents) → can go unpublished, `verified` (`is_verified` flipped by admin, a one-way trust upgrade), suspended/deactivated (`is_active = false`), soft-deleted (`deleted_at`), or purged. **The two gates that matter:** `is_complete` controls whether the brand can transact; `is_published` controls whether talents can see it.

**Brand aesthetics lifecycle**
`empty → set at onboarding (Step 4) → refined` (edited in the profile editor, or implicitly enriched from browsing) `→ edited freely thereafter`. One-to-one with the brand, so it's created once and updated in place — no terminal state of its own beyond the brand being deleted.

**Brand images lifecycle**
`uploaded → processed (thumbnail generated) → ordered (position) → displayed → replaced/removed`. Capped at the 2–3 the brand provides; swapping one out is a delete-and-re-add.

**Brand creative needs lifecycle**
`set at onboarding (Steps 3 + 5) → active (driving the discovery feed) → adjusted as the brand's hiring changes`. Like aesthetics, one-to-one and update-in-place; `budget_tier` shifts as the brand grows.

**Brand credibility lifecycle**
`initialized at zero → accrues over time`. Counters move only on real events: `completed_projects_count` increments when a contract completes; `response_rate_pct` / `avg_response_time_hours` recalculate as the brand replies to enquiries; `brief_quality_score` updates internally. It never resets — a monotonic trust record (response rate can dip, but the project count only climbs).

**Brand review lifecycle**
`submitted by talent (is_approved = false) → pending/moderation → approved (public) or rejected/hidden`. Triggered when a contract hits `completed` — the moment a talent can rate the brand on the three axes. Mirrors the talent-side review lifecycle exactly.

**Brand social handle lifecycle**
`added (settings-stage) → displayed → edited → removed`. A simple list item, reorderable by `position`; no states beyond present/absent.

**Brand signal lifecycle**
`emitted → stored (immutable)`. Each view/save/brief is a one-time append-only event — never edited or transitioned, it just accumulates and feeds the preference engine. Effectively write-once; the only "lifecycle" is eventual aging-out/archival if moved to an analytics store.

**Project lifecycle**
`draft → open (published, accepting/seeking talent) → in_progress (contracts running under it) → completed` → can also be `cancelled` from any active state. Soft-delete (`deleted_at`) for removal. Independently, `is_public` toggles listed ⇄ private without changing the status. A completed project becomes a showcase on the brand profile.

**Project media lifecycle**
`uploaded → processed → ordered (position) → displayed → removed`. Same shape as brand images, scoped to the project.

### How they connect
The **brand lifecycle is the spine** — `is_complete` gates transacting, `is_published` gates visibility. The **project lifecycle runs on top** (a brand spins up many projects over its life). Both the **credibility** and **review** lifecycles are consequences of contracts completing — they don't advance on their own; they advance when the contract lifecycle reaches `completed`. Aesthetics, creative needs, images, and social handles are all **update-in-place satellites** of the brand with no terminal state of their own; **signals are append-only**. The one entity with no real lifecycle is `brand_signals` — it's logged, not transitioned.
