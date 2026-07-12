# Fama — Talent Spec (Pages · Workflows · Lifecycles)

> Canonical description of the talent side of Fama. Transcribed and lightly cleaned (typos fixed, formatting normalized) from the original design docs. Also documents the shared **deal engine**, which the talent deal room consumes. Pair with `schema-master.md` and `brand-spec.md`.
>
> **Removed features (`docs/decisions.md` ADR-K/L/M):** the **rate card / services**, **availability & travel**, and **affiliations & press** features were removed entirely. Deal amount now comes from the flow's form/quote step (not a service); enquiries are no longer gated by availability. A single indicative **Pricing rate** (`rate_unit`/`rate_amount`/`rate_currency`, ADR-N) replaces the rate card and `rate_tier`.
>
> **Editor consolidation & Skills rename (`docs/decisions.md` ADR-N):** "Professions" is now called **Skills** everywhere (route segment `professions` → `skills`; the `talent_types` table is the Skills catalog). The standalone **Professions** and **Account** tabs were folded into the **Profile editor**, so the talent sidebar is: **Home · Profile · Content · Reviews · Deals**. The Profile editor now holds Identity, Skills, Username (the public `slug`, relabelled), Publish, Pricing rate, and Blocks. The sections below have been updated to match.

---

## PAGES

### Public pages (no login wall)

**Talent profile page — `fama.com/{slug}`**
The core product. An **Instagram-style, avatar-led header** (no cover/hero image — ADR-O), token-only and dark/light/RTL-aware:
- A large **circular avatar** (from the `avatar` media collection; initials/gradient fallback) — there is **no cover banner**.
- **display_name** + **@username** (the `slug`) + an optional "…" menu (copy profile link).
- A secondary line: the primary **Skill** (`talent_talent_type.is_primary`) or the headline.
- A three-item **stats row**: **Projects** (count of `projects`) · **Views** (`view_count`) · **Rating** (avg of approved reviews, hidden when there are none).
- The **Pricing rate** (ADR-N) shown near the identity as a "From {currency} {amount} / {unit}" chip — hidden entirely when unset.
- The **bio** and an optional external-link row (an external portfolio/booking URL if one exists). *(The header no longer shows skill chips — the prominent skill **tab bar** is the profile's navigation.)*
- **CTAs**: primary **Message** (the brand↔talent chat entry — ADR-P) + secondary **Leave a review** (opens the review form). **Message** points at the reserved `brand.talents.message` route: a visitor who isn't a brand is sent to brand auth (with this profile stored as the return URL); an authenticated brand hits an interim "Messaging coming soon" stub. The real chat / deal initiation attaches there later. *(Message supersedes the earlier "Contact" CTA.)*

The profile is **two stacked regions** (ADR-R):

**Region 1 — identity & universal (always visible):** the header (above) and the universal/meta data (location, **Pricing rate**), then the **profile-level blocks** (`talent_type_id = NULL`, `is_visible = true`, in `position` order).

**Region 2 — skill tabs (the profile's primary navigation):** a tab bar with **one tab per linked skill that has visible blocks** (ordered `talent_talent_type.position`, primary first and **active by default**); a skill with no visible blocks has **no tab**, and a **single-skill** talent shows no tab bar (its blocks render directly). A tab's panel = that skill's `is_visible` blocks in `position` order (skipping the `hero` block, which the header replaces), each rendered by its block-type partial — grandfathered (deactivated) types still render. Because gallery items are block-scoped (`portfolio_items.block_id`) and projects are skill-scoped (`projects.talent_type_id`), each tab shows its **own** body of work, and a tab's **Projects** block links to the unchanged detail page `/{slug}/work/{project}`.

The tab bar is presented as **primary navigation**: a **sticky** (under the site header), horizontally-scrollable **segmented / pill** control, separated from the identity region by a divider. Each tab shows the skill's **`icon`**, its **name**, and a **count badge** (visible blocks in that skill). The **active** tab is **filled** (accent surface + contrasting label + weight) — not a faint underline; inactive tabs stay legible, with hover + `focus-visible` states. The panel renders the **active skill's name as a heading** so the context is explicit even when the bar scrolls out on mobile, and switching fades the panel (reduced-motion-aware). **A11y:** proper `role="tablist"` / `role="tab"` / `role="tabpanel"` with `aria-selected`, `aria-controls`/`aria-labelledby`, **roving `tabindex`**, and **arrow / Home / End** keyboard navigation (RTL-aware; activation follows focus). On small screens the tabs **scroll horizontally with snap + edge fades** and never wrap.

**Loading & deep-linking:** the active (primary, or `?skill=`) tab renders **server-side**; other tabs are fetched on first click via `GET /{slug}/tab/{skill}` (envelope, eager-loaded, no N+1) and cached client-side (re-clicking is instant). The active tab is reflected in the URL (`?skill={slug}`) so it's shareable and the back button works; `view_count` bumps once per profile view (not per tab switch). No availability badge and no services/affiliations/press blocks (ADR-K/L/M). Tab switching is Alpine-only (no reload).

**Project detail page — `fama.com/{slug}/work/{project}`**
One `projects` record expanded: title, client, role, cover image, full body, results metrics. The one block rich enough to warrant its own URL.

**Deal initiation (booking CTA) — now starts a deal**
The booking CTA no longer just sends an enquiry. Driven by `booking_type`/`booking_value`, it now creates a `deals` row: the brand picks the applicable `deal_flow`, which snapshots into `deal_steps` and opens the deal room. Enquiries are always allowed (no availability gate). The deal amount is captured by the flow's form/quote step. (This is the old "booking/enquiry page," upgraded.)

**Review submission page**
Public form a past client uses → writes to `reviews` with `is_approved = false` (pending). Captures reviewer name/role/company, rating, body, project type.

**Discovery / search page** — **skills-first** (public, `App\Queries\TalentSearch`)
The primary filter is **Skills** (the `talent_types` catalog), presented as **the** primary control: a **sticky** bar (sticks under the site header while results scroll) with a clear **"Skills"** heading, a **selected-count** badge, and an **"All"** reset chip that sits **beside** the scope groups (a divider separates it from the first group). "All" is a **neutral reset action** — it is **not a default selection** (it shows no filled/selected state and is disabled while nothing is chosen), so an unfiltered view highlights no chip. Skills render as **multi-select chips grouped by scope** (Modeling / Crew / Creative — the category display labels), with the scope groups laid out **side by side** on one line (columns separated by dividers; the sticky bar keeps them on a single scrollable row). Each chip shows its `talent_types.icon` and real **states**: hover, `focus-visible` ring, and an unmistakable **selected** state (filled accent + check). Chips are accessible **toggle buttons** (`aria-pressed`) inside a labelled `role="group"`, fully keyboard-operable. Beneath the bar an **active-filter summary row** lists every active filter as a **removable chip** ("Modeling ×", "Cairo ×") with **"Clear all"**. A live **result count** ("N talents") sits above the grid and refreshes with the results. The free-text `q` search is demoted to a small **secondary** control in the bar.

Applying any filter updates the grid over **Ajax** (envelope, paginated, eager-loaded — no reload) with **skeleton loaders** while loading, and an **empty state** (with a "Clear filters" action) when nothing matches. Active filters **sync to the URL** query string, so a filtered view is **shareable** and the **back/forward buttons** restore it (discrete changes push history; typing in the search replaces it; pagination holds the filters).

An **"Advanced filters"** button (with an active-advanced-filter count) opens a **modal** holding the full filter set. The dialog is **teleported to `<body>`** (`x-teleport`) so no transformed/`overflow` ancestor can trap it; it always opens **centred in the viewport** regardless of scroll, over a token **scrim** (`--scrim`). It **locks body scroll** (restoring position on close), closes on **×, backdrop click, or ESC**, **traps focus** (returning focus to the trigger), is `role="dialog"` + `aria-modal` + labelled by its title, and renders as a **bottom sheet with its own internal scroll on small screens**. Enter/leave animate from the motion tokens and honour `prefers-reduced-motion`. The modal **also holds the Skills selector** (the same grouped chips as the bar) so a visitor can pick skills right there. Unlike the sticky bar (which applies live), **the modal is a staging area**: it edits a **draft** snapshot of the applied filters, and **nothing commits to the results until "Apply filters"** — closing via ×/backdrop/ESC discards the draft; "Clear filters" resets the draft in place (still not applied). The modal is a **wide** dialog (`max-w-3xl`) with a title + subtitle, a **Skills** section, a divider, a **Location** section (`city`, `country`), and a **Skill-specific** section whose scoped `<select>`s sit in a 2-column grid.

The **skill-specific scoped filters** appear **only for the selected (draft) skills' categories** — with **no skill selected** the section shows a hint ("Select a skill to reveal its filters."); **picking a skill reveals the filter that narrows it further**:
- **Crew scope → Equipment** (`equipment.category`).
- **Creative scope → Software** (`software_stack.software_name`).
- **Modeling scope → Looks** (`look_types.name`) — matched on the English name path, backed by a functional index (`docs/schema.md`).

All filters are whitelisted in `TalentSearch` (`type` — multi-select, comma-separated slugs; `category`, `city`, `country`, `equipment`, `software`, `looks`, `q`); results sort by `-view_count` and paginate 12/page. *(Availability filtering was removed — ADR-L.)*

*Future enhancement:* comp-card attribute **ranges** (e.g. height/measurements) for model-scope filtering — not built yet; would add range params + indexes on `comp_cards`.

### Authenticated — talent dashboard

**Dashboard home**
Status overview: draft vs live (`is_published`), `view_count`, pending reviews count (`is_approved = false`), and active deals + whose turn it is (replacing the old "recent enquiries").

**Profile editor** *(the single profile surface)*
Holds everything about the profile in one page:
- **Identity** — the **profile image** (avatar) uploader (upload / change / remove → the `avatar` single-file media collection; Ajax, no reload; falls back to the initials avatar) plus core `talents` fields (display name, headline, bio, base city/country, booking method) and **Username** (the public `slug`, relabelled "Username" in the UI + validation; still unique + auto-generated if blank). *(Only the circular avatar — the cover/hero uploader was removed with the IG-style header, ADR-O.)*
- **Skills** — add/remove skills, mark one `is_primary`, order them (`position`). Adding a skill seeds **that skill's** `default_blocks` into its **own tab** (per-skill, ADR-Q). Removing a skill deletes its tab's blocks but preserves content (items un-linked, projects un-scoped) — the UI confirms first. (Skills persist through `talent_types`, the Skills catalog.)
- **Blocks** — organised by scope: a Universal / profile-level section plus one section per skill (primary first). Add / reorder (within a scope) / hide / remove a block, or **move** it between scopes — the picker offers only blocks eligible in that scope, and `is_repeatable` now means "once per scope".
- **Publish** — the `is_published` toggle (Live ⇄ Unpublished), guarded by the profile state machine.
- **Pricing rate** — an indicative rate (`rate_unit` ∈ {project, day, hour}, `rate_amount`, `rate_currency`), all-or-nothing (a complete rate or none). NOT translatable. Public display is a later prompt.
- **Blocks** — the live, reorderable view of `profile_blocks` (add, fill, drag `position`, toggle `is_visible`). The block picker is driven by the `block_types` catalog: only blocks that are `is_active` and that the talent is eligible for (universal, or gated to one of their categories/skills), omitting non-repeatable blocks already present.

**Block content editors** *(surfaced by the talent's skills/categories)*
Gallery (`portfolio_items`), Comp card (`comp_cards`), Looks (`look_types`), Digitals (`digitals`), Showreel (`showreels`), Equipment (`equipment`), Projects (`projects`), Software (`software_stack`), Brand collabs list management. Which editor opens is informed by `block_types.content_source` (inline JSON vs. linked child table).

**Reviews manager**
Moderation queue: pending (`is_approved = false`) → approve/reject; view approved.

*(The old standalone **Professions** and **Account** tabs are gone — folded into the Profile editor as the Skills, Username, and Publish sections above.)*

**Deal room (talent view)**
The single deal page, laid out **timeline-first**: a header on top (reference, title, counterparty, status badge, amount, "← All deals" link), then the **`deal_messages` timeline as the central, focal conversation view** (free-text messages and system_events interleaved chronologically, newest at the bottom) with the message composer. A narrower **side panel** carries (a) the current-step **action panel** at the top — rendered by `step_type` (send quote, approve, upload, sign, pay, "waiting on X") and adapting to whose turn it is — then (b) the **phases stepper** below it (the `deal_steps` progress list with actor labels, completed ticks, active-step highlight, and skipped/rejected states). On narrow screens the side panel stacks under the timeline. Messaging and step actions are Ajax (no reload).

**Deals inbox (talent view)**
List of the talent's deals with status, current step, and whose turn (`awaiting_talent` highlighted). Filter by status.

*(Account settings — Username/`slug` and the publish toggle — now live in the Profile editor, not a separate page.)*

### Authenticated — brand dashboard *(brand-side pages that touch the shared deal room)*

**Brand dashboard home** — active deals overview, whose turn, recent talent viewed.
**Deals inbox (brand view)** — the brand's deals with status and current step (`awaiting_brand` highlighted).
**Deal room (brand view)** *(Phase 2C — not built yet)* — the same timeline-first deal page, brand-side: submit brief, review/accept quotes, pay, sign — driven by the current step's `actor`/`step_type`. When built it should reuse the talent deal-room layout (timeline central; action panel + phases in the side panel) so both sides stay in sync.

---

## WORKFLOW

### Admin / system workflows

1. **Build a deal flow** *(the configurable "chat steps")* — Admin creates a `deal_flow` → adds ordered `deal_flow_steps`, each with an `actor` (brand/talent/both/admin/system), a `step_type` (form, approval, upload, payment, contract, message, schedule, info), `is_required`/`is_skippable`, and per-step `settings` → marks one default or scopes it by `applies_to` category → activates. Ongoing and editable; edits apply to **new deals only** (steps snapshot at creation).
2. **Profile moderation** — review profiles → suspend/unpublish (`is_published`) → soft-delete (`deleted_at`).
3. **Skill template management** — edit a `talent_types.default_blocks` → changes what new talents of that skill get seeded → add skills without code.
4. **Review moderation queue** — collect all `is_approved = false` → approve/reject in batch.
5. **Media processing (background)** — on upload → queue job → generate thumbnails, validate, write URLs back to `portfolio_items`/`digitals`.
6. **Admin deal intervention** — open any deal → override a stuck step's status, act as the admin actor where a step requires it, nudge/reassign/cancel.

### Brand-side workflows

7. **Discover & view a talent** — land on `fama.com/{slug}` → read profile.
8. **Initiate a deal** *(replaces the old fire-and-forget enquiry)* — click the booking CTA → pick the applicable `deal_flow` → `deals` row created → flow steps snapshot into `deal_steps` → first step activates, status set to that step's actor → deal room opens. The deal amount is captured by the flow's form/quote step.
9. **Work the deal (brand turn)** — when a step's actor is brand/both → perform the action in the deal room (submit brief, review/accept a quote, sign, pay) → step marked completed, data saved to its `payload`, a `system_event` posted → deal advances to the next actor.

### Talent-side workflows

10. **Onboarding / profile creation** — sign up → select one or more talent types (mark one `is_primary`) → system merges `default_blocks` of all selected types and dedupes → seeds `profile_blocks` → fill core `talents` fields → fill type-specific blocks → set booking method → publish.
11. **Manage skills** — add a skill (merges its `default_blocks`, seeds missing blocks) → remove → reorder (`position`) → change `is_primary`. `UNIQUE(talent_id, talent_type_id)` blocks duplicates. (Surfaced as the Skills section of the Profile editor.)
12. **Build the profile (blocks)** — within a scope (a skill's tab or the universal section): add block (picker is scope-eligible) → pick `block_type` → fill content → drag to reorder (per-scope `position`) → toggle `is_visible` → optionally **move** to another scope. `is_repeatable` = "once per scope" (ADR-Q).
13. **Add portfolio work** — open gallery → upload → thumbnail generated → attach credits/tags → order. Same shape for digitals (own section, `shot_type`).
14. **Model setup** *(any model-category type)* — fill the single `comp_cards` record → add `look_types` → upload `digitals`.
15. **Crew/creative setup** *(any crew/creative-category type)* — add `showreels` → list `equipment` by category → write `projects` → add `software_stack`. A model-photographer runs 14 **and** 15.
16. **Work the deal (talent turn)** — when a step's actor is talent/both → perform the action in the deal room (send quote, accept brief, upload deliverables, sign) → step completed, payload saved, `system_event` posted → deal advances.
17. **Reviews moderation** — pending (`is_approved = false`) → approve/reject.

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

**Block lifecycle**
`seeded into a skill's tab (from that skill's defaults) or added to a scope → edited → visible ⇄ hidden (is_visible) → reordered within its scope (position) → moved between scopes → removed`. Blocks are skill-scoped (ADR-Q): `talent_type_id` NULL = profile-level; a skill's removal deletes its tab's blocks but preserves the underlying content.

**Review lifecycle**
`submitted (is_approved = false) → pending/moderation → approved (public) or rejected/hidden`. Now naturally triggered when a deal hits `completed`.

**Skill link lifecycle** (`talent_talent_type`)
`attached (one marked is_primary) → reordered (position) → primary reassigned → detached`. Attaching merges new default blocks; UNIQUE prevents duplicates.

**Media / portfolio lifecycle**
`uploaded → processed (thumbnail generated) → ordered (position) → visible → archived/deleted`.

**Onboarding lifecycle (account level)**
`sign up → select talent type(s), mark primary → blocks auto-seeded (merged) → fill core fields → fill type-specific blocks → set booking method → publish`.
