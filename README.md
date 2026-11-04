# Fama

Fama is a **creative talent marketplace** — Egypt-first, MENA-wide. It connects **talent** (models,
photographers, cinematographers/DOPs, stylists, creative directors, graphic designers) with **brands**
through rich, malleable talent profiles, a discovery feed, and an admin-configurable **contract engine**
that walks a brand and a talent through booking, quoting, contracting, payment, delivery, and reviews.

> **Canonical reference:** [`docs/specs/`](docs/specs/) is the single source of truth for the data
> model, pages, workflows, and lifecycles. Read the relevant spec before building anything. Project
> laws live in [`CLAUDE.md`](CLAUDE.md); architecture/decisions in [`docs/`](docs/).

## Status

**UI: the fama-ui design (2026-10-12, ADR-AB).** Talent, feed, public profile, auth and error pages use the
client's fama-ui kit (`public/fama-ui/`, `<x-fama.layout>`), synced to the co-founder's prototype as of 1 Nov
2026 (ADR-AD). Talent nav: a **side bar** on wide screens (account · Create · Feed · Notifications · Activity ·
Projects · Credits · Edit profile · View profile), a top bar ≤1100px. A talent signs in to the Feed once the profile
is done, else to Profile structure; **going Live = three saved steps** (name, a skill with a speciality, city —
no admin review, ADR-AC). Full Pest suite **897/897 green**.

Demo logins (password `password`): `talent-demo@fama.test` (every screen populated), `talent-new@fama.test`
(Private — the go-Live steps), `brand-demo@fama.test`, `admin-demo@fama.test` (at `/admin/login`).
Top up an existing dev DB with `php artisan db:seed --class=ScreensDemoSeeder`; if `/feed` looks empty (seeded
content older than the 30-day ranking windows), run `php artisan demo:freshen`.

**Talent, brand and admin slices — complete (production-grade).** Full Pest suite **345/345 green**;
`php artisan migrate:fresh --seed` seeds demo data end-to-end. Blade + Alpine on the shared `http.js`
(no page reloads), JSON envelope, i18n (EN/AR + RTL), light/dark. Manual QA checklist:
[`docs/conventions.md`](docs/conventions.md#qa-checklist--talent-slice-manual).

- **Talent** — public pages (profile, project detail, review, enquiry, discovery/search) and the
  dashboard (**Home · Profile structure · Profile content · Projects · Reviews · Contracts**), where the
  **Profile editor** is the single profile surface (identity, Skills, Username, publish, pricing rate,
  blocks) and **Projects** (`/talent/projects`) owns the portfolio of work — every project tagged with
  **at least one skill** (ADR-X), which is what places it in the public profile's tabs. The dashboard
  home carries a public/private visibility switch and a views KPI with a week-over-week delta that
  expands into a range-filterable chart (back to the profile's creation date).
- **Brand** — core + satellites, **projects**, the brand contract room, brand discovery, plus the
  talent-facing brand/opportunity pages and the apply flow (rich-text brief with @-mentions +
  attachments, sanitized server-side).
- **Admin** (`admin` guard + spatie/laravel-permission RBAC) — flow builder, moderation queues (incl. a
  **global review queue** across talent + brand reviews), projects oversight, the **contract intervention
  console**, activity log, settings, and admin users, all reachable from a permission-gated sidebar +
  dashboard cards. **Verification is a hard go-live gate (ADR-U):** no talent or brand reaches the public
  until an admin **Verifies** it (`is_verified` guards the publish transitions); moderators can also
  remove a review with an emailed reason notice (live-preview composer). **Contract templates (ADR-V):**
  a two-column builder authors the contract "schema" (standard terms + custom clauses) with a live
  document preview; each contract flow links a template whose terms freeze onto every contract it creates. **Block governance is split (ADR-T):** the **Block catalog** (`/admin/blocks`,
  `manage-blocks`) owns which blocks exist and who may use them (eligibility + grandfathering); the
  **Skills templates** page (`/admin/skills`) only picks each skill's preselected blocks and their order
  from the catalog-eligible set.
- **Contract engine** — admin-authored flows → steps, StepHandler strategy, state machines. Contracts
  **snapshot** their steps at creation, so editing a flow only affects future contracts.

> **Naming:** two repo-wide renames are done — **Campaign → Project** and **Deal → Contract**. Two things
> intentionally keep the old word: the `talent_types.category` enum (`model|crew|creative`) and the
> `project_types` enum value `campaign_video`. `docs/changelog.md` is dated history and still says
> "deal" in entries before 2026-07-15.

> **Removed (`docs/decisions.md` ADR-K/L/M):** the rate-card / services, availability & travel, and
> affiliations & press talent features were removed entirely. Contract amount comes from the flow's
> form/quote step; enquiries are no longer gated by availability.
>
> **Skills + editor consolidation + Pricing rate (ADR-N):** "Professions" is renamed **Skills** across
> the UI/routes (the `talent_types` table is the Skills catalog); the Professions + Account tabs were
> folded into the Profile editor; the public `slug` is shown as **Username**; a new indicative
> **Pricing rate** (`rate_unit`/`rate_amount`/`rate_currency`) replaces the rate card.
>
> **Instagram-style profile header (ADR-O):** the public profile leads with an avatar-based IG-style
> header — **no cover/hero image** (the `hero` collection + `hero_image_url` accessor + editor uploader
> are gone; `avatar` stays). Header = circular avatar + display_name + **@username** + primary-skill
> line + a **Projects · Views · Rating** stats row + bio + optional link + the **Pricing rate** chip +
> Message / Leave-a-review CTAs. Token-only; verified dark + light + RTL.
>
> **Skill-scoped blocks & projects (ADR-Q, extended by ADR-X):** `profile_blocks` belong to a skill
> (`talent_type_id`; NULL = profile-level). Adding a skill seeds **its own tab's** blocks (per-skill —
> gallery in both tabs); the picker is per-scope, blocks move between tabs, and removing a skill deletes
> its tab's blocks but preserves content. The editor manages blocks per scope. A **project** carries
> **one or more** skills (`project_talent_type`; `projects.talent_type_id` is its home tab) — so a
> project tagged with two disciplines appears in **both** tabs, and removing a skill re-homes its
> projects on the next tag rather than dropping them.
>
> **Public profile — two regions (ADR-R):** Region 1 = the IG identity header + universal/meta (location,
> pricing rate, clickable skill chips) + profile-level blocks; Region 2 = **skill tabs** (primary active
> by default, tabs only for skills with visible blocks). The active tab renders server-side; others
> **lazy-load** on click (`GET /{slug}/tab/{skill}`, no reload) and cache; the active tab is deep-linked
> in the URL (`?skill=`, shareable + back button). A tab shows every project tagged with its skill.
>
> **Skills named as disciplines (ADR-S), nine of them (ADR-Y):** the Skills read as the
> **discipline/activity** — Photography, Cinematography, Direction, Modelling, Styling, Hair & makeup,
> Creative direction, Graphic design, Retouching & post (slugs `photography` / `cinematography` /
> `direction` / `modeling` / `styling` / `hair-makeup` / `creative-direction` / `graphic-design` /
> `retouching-post`), not person-nouns. `talent_types` IDs are unchanged so all FKs are intact; the
> `category` enum (`model/crew/creative`) is unchanged (only its display labels are
> Modelling/Crew/Creative). The ADR-S rename broke old `?skill=` deep links once (no redirects) — which is
> why ADR-Y's "Modeling" → "Modelling" pass changed the **display name only** and left the slug alone.
> **AR names are a first pass — to be confirmed.**

> **A project is six things (ADR-AA):** client · description · media · collaborators · skills &
> specialities · date. **Collaborators** is the substantial one: a credit is `person + skill + role`,
> where skill says what someone *can* do and role says what they did *here*. Crediting someone who is on
> Fama writes the project onto their portfolio, so it **requires their confirmation** and stays invisible
> everywhere — the public project page included — until they accept; otherwise anyone could attach a
> name to work they did not do. Crediting someone who is not on Fama is a plain name: display-only,
> never leaves the project, nothing to confirm. Declining and withdrawing are the same action, so being
> publicly associated with a project stays the collaborator's call. **Media** is the ordered work, and
> the order is the edit — the first frame is chosen, not defaulted. The **date** is recorded and never
> displayed, so work can be ordered by recency without an older strong project being discounted for its
> age. The **client** is hidden when empty: no "personal project" placeholder, because the absence says
> it.

> **The portfolio is six sections (ADR-Z):** **Now** · **About** · **Skills** · **Reviews** ·
> **Recognitions** · **Willing to explore**. Sections that describe the page live in the header; sections
> that are content are blocks — the client spec was mapped ONTO the block system, not in place of it.
> **Now** is the time-sensitive strip: `will_travel` (a yes/no, never a list of cities) and
> `is_available`, whose `availability_changed_at` stamp moves only when the toggle actually flips, so the
> public profile can honestly say "as of …". This supersedes the availability half of ADR-L with new,
> simpler columns — but availability is still **information, never a gate**: it never blocks an enquiry.
> **About** adds `worldview` (taste and point of view, deliberately not a second bio — the editor prompts
> it with specific questions) and social links. **Recognitions** are peer vouches, and the distinction
> from reviews is entirely *who writes them*: a review is **brand → talent** after a booking completes
> here, so it cannot exist at launch; a recognition is **talent → talent**, needs no booking, and works
> from day one. Both use the same admin moderation queue, and they must render differently — no stars,
> quote-led, relationship first — or the page becomes one wall of praise. **Willing to explore** is
> structured (a skill + optional kind of work, capped at six) so brands can filter it, and its skill is
> deliberately *not* restricted to ones the talent already lists — naming a direction you have not worked
> in yet is the point.

> **Specialities — the second level of the vocabulary (ADR-Y):** each Skill nests into **4–7
> specialities** and the tree stops there. The list is **controlled** — talents pick from it and can never
> type into it, because an invented entry would not match on the project side and every filter that
> depends on the same name appearing on both would silently stop working. Every entry answers one
> question: **what kind of subject** (output format — campaign, editorial, lookbook — belongs to the
> project). A speciality `slug` is unique **per skill, not globally**: "Fashion" lives under photography,
> modelling *and* styling, and that overlap is what lets one search assemble a whole crew. `is_regional`
> flags the entries added for this market specifically (**Modest fashion**, **Arabic lettering**).
> Governance sits with the block catalog at `/admin/skills`: the slug is **locked** once created, and
> retiring an entry (`is_active = false`) hides it from every picker while existing claims keep rendering
> — it is never a delete. Discovery filters on it via `filter[speciality]`.

> **Projects (brand side):** one project = **one role = one position** (`brand_projects.talent_type_id`).
> `budget_is_public` defaults to **false** — a private budget is withheld from every non-owning viewer in
> the payload, not merely hidden in the view. A contract links to its project via
> `contracts.brand_project_id` (ADR-F, landed).

**Phase 5 complete — the public Feed** (spec: `docs/specs/feed-spec.md`; rulings ADR-F1…F8;
performance contract: `docs/performance.md`; design: `docs/design-system.md`): talents publish
notes, media sets and Project shares; talents + brands follow, like, comment and reply (two-level
threads with tombstones); guests read everything and any write is captured, replayed after login,
and returned to the exact post anchor. Two-tier seeded ranking with provably-stable cached-ID
pagination, casting strips, filtered modes (`?skill=`, `?following=1`), mention/link parsing with
bidi-safe rendering and SSRF-guarded enrichment, aggregated database notifications, the feed-scoped
**Daylight** design system (notched frames, blue shadows, editorial credits), a moderation console
with an auto-hide threshold, and an `/api/v1` Sanctum mirror documented by Scribe
(**190 endpoints**, 45 domain tables + the notifications table). A revision batch (5S–5Y) re-grounded
the palette in the topbar colour with a test-enforced WCAG contrast gate, unified the mention
autocomplete, made chips fully replace raw URLs (inline SVG glyph sprite), rebuilt Project spreads
as full previews, added private **Saved/Liked** collections (bookmarks, ADR-F9) and the feed's
motion layer (entrances, the light response, condensing chrome) — 4 more endpoints + the
`bookmarks` table. The **feed rails** (5Z–5AA) followed: a tiered, reason-carrying follow-suggestion
engine (dismissals, truthful bilingual reason lines, per-actor caching) feeding two sidebars —
portrait TALENT TO FOLLOW inline-start, square BRANDS TO FOLLOW inline-end — with talent→brand
following enabled (`brands.followers_count`), optimistic follow/dismiss with undo, guest gating
with intent replay, and responsive fallbacks to inline strips (**200 endpoints** total).
**Round 3** (5AB–5AI) finished the interaction surface: a single z-index scale with a fixed
opaque topbar (Feed-first nav, scroll hide/reveal), floating New-post/back-to-top actions, a
rebuilt action bar with a share popover and overflow menu, generic media-grid geometry with a
teleported lightbox and per-image ALT text, a seven-reason config-driven report flow with honest
confirmations and a toast rack, a reworked comment thread (collapsed composer, optimistic
pending rows with retry), the mention autocomplete portalled above every clipping container
(browser-verified), and a rebuilt New-post modal (pinned chrome, real-grid media preview,
publish hints, dirty-close confirm) — plus a `window.famaLang` bridge so every runtime string
localizes, and a full Arabic pass over the feed's UI copy. A **dashboard follow-management** surface
(FOLLOWING / FOLLOWERS sub-tabs on both the talent and brand dashboards) then turned the follow graph
into a pruning tool: mixed-type cards shaped by type (portrait talents, square brands), a denormalized
`talents.last_posted_at` powering a "quiet since" signal and a least-active sort, a mutual "follows
you back" badge, server-side search / type / Skill filters, optimistic unfollow with in-place undo,
and a strict privacy boundary keeping the dashboard-only signals out of the public listing.

**Deferred — Phase 4:** the full mobile API (Sanctum tokens + Scribe docs) over the existing
services/resources (the feed's `/api/v1` mirror lands with Phase 5, Prompt 5K); and wiring the public
profile's interim **Message** CTA (ADR-P) into the real brand→talent messaging / contract-initiation
flow it reserves.

## Stack

- **PHP 8.3+ / Laravel 13**
- **Auth:** Laravel Breeze (Blade + Alpine + Tailwind + Vite); multi-guard; **Sanctum** for the mobile API
- **Front-end:** Blade + Alpine, **Tailwind v4** (Vite), dark mode (class strategy), full RTL
- **i18n:** `mcamara/laravel-localization` (locale-prefixed routes) + `spatie/laravel-translatable`
- **Domain packages:** `spatie/laravel-medialibrary`, `laravel-model-states`, `laravel-data`,
  `laravel-activitylog`, `laravel-query-builder`
- **API docs:** `knuckleswtf/scribe` · **Tests:** Pest
- **DB:** MySQL (dev database `fama`, test database `fama_test`); any Laravel-supported driver works

## Setup

```bash
composer install
cp .env.example .env          # if you don't have a .env yet; set the MySQL DB_* values
php artisan key:generate
mysql -u root -e "CREATE DATABASE IF NOT EXISTS fama; CREATE DATABASE IF NOT EXISTS fama_test;"
php artisan migrate           # dev DB (fama)
php artisan db:seed           # optional: catalogs + demo talent
npm install
npm run build                 # or `npm run dev` while developing
```

## Run

```bash
composer dev                   # serve + queue + logs + vite (all-in-one)
# or individually:
php artisan serve
npm run dev
php artisan test               # Pest suite — runs on MySQL (fama_test, per phpunit.xml)
```

## Outgoing mail (ZeptoMail)

Mail goes out over SMTP through ZeptoMail (`smtp.zeptomail.com:587`, username `emailapikey`, password = a
send-mail token). Everything is env-driven (`MAIL_*` in `.env.example`) — switching provider is an `.env`
change only. Laravel 13: leave `MAIL_SCHEME` null on 587 (STARTTLS is automatic); `smtps` only for 465.

- **Sender profiles** (`config/mail.php` → `senders`: `system` · `support` · `info` · `contact`) set From +
  Reply-To per message via `App\Mail\MailSender` — `MailSender::envelope($subject, 'system')` in a Mailable,
  `MailSender::apply($mailMessage, 'support')` in a Notification's `toMail()`. `system` = no-reply From with
  Reply-To `MAIL_SUPPORT_ADDRESS`.
- **Design:** all mail (incl. password reset) renders in one branded shell — `<x-email.layout>` + button/
  callout/panel components; brand values in `App\Mail\EmailTheme`. `php artisan mail:test` doubles as a preview.
- **Queued:** Mailables implement `ShouldQueue` + `afterCommit()`; a worker must run (`composer dev` starts one).

```bash
php artisan mail:test you@example.com            # send now, print success or the exception
php artisan mail:test you@example.com --queue    # through the queue
php artisan mail:test you@example.com --sender=support
php artisan queue:work --tries=3                 # deliver queued mail
```

## Release feature flags (V1 scoping)

`config/features.php` gates whole product areas so a release can ship a subset while the rest stays in the
codebase, dormant. Flags **default ON** (dev/staging + the test suite run the full app). The **V1 production
release** ships only the talent side, the public feed, and the public talent profile — set in production `.env`:

```dotenv
FEATURE_BRANDS=false
FEATURE_CONTRACTS=false
FEATURE_MESSAGING=false
```

Enforcement is runtime: `App\Support\Features`, the `feature:<name>` route middleware (disabled → 404), and
the `@feature('brands')` Blade directive. Re-enable a feature later by flipping its env var (contracts and
messaging imply brands). Reviews and the talent "Brands I've worked with" block stay in V1; admin stays fully
functional (talent verification + feed moderation). A production V1 seed runs only the catalog/config seeders
(Roles/TalentType/BlockType/Settings). See ADR-W in `docs/decisions.md`.

## Three-guard auth model

Fama has **three login entities**, each with its own session guard + Eloquent provider
([`config/auth.php`](config/auth.php)):

| Guard | Provider → Model | Table | Web home |
|---|---|---|---|
| `admin` (default) | `users` → `App\Models\User` | `users` (migrated) | `/admin/dashboard` |
| `brand` | `brands` → `App\Models\Brand` | `brands` + satellites & projects (Phase 2A–2C) | `/brand/dashboard` |
| `talent` | `talents` → `App\Models\Talent` | `talents` (Phase 1A) | `/talent/dashboard` |

- **Login** is a single, role-aware form: the submitted `role` selects the guard; absent `role`
  defaults to `admin` (the only migrated auth table in Phase 0). `route('dashboard')` dispatches to the
  active guard's dashboard.
- **Dashboards** are guarded route groups (`auth:admin` / `auth:brand` / `auth:talent`). Public pages
  (home now; public talent/brand profiles in Phase 1) are unguarded.
- **Mobile API** authenticates with **Sanctum** tokens (Phase 4); all three models use `HasApiTokens`.

## Folder structure (Fama additions)

```
app/
  Actions/Contracts/Action.php     # the single-purpose action *interface* (Laravel's Contracts convention)
  Actions/Contracting/…            # snapshot / initiate / advance / reject / convert
  Contracting/                     # contract engine: StepHandler strategy + factory + progression
                                   #   (NOT App\Contracts — that namespace is for interfaces)
  States/{Contract,ContractStep,ContractMessage,ContractFlow}/…  # contract lifecycle state machines
  Data/BaseData.php                # base DTO (spatie/laravel-data)
  Enums/UserRole.php               # role ⇄ guard source of truth
  Http/
    Controllers/Auth/…             # guard-aware Breeze auth
    Requests/Auth/LoginRequest.php # role-aware login
    Resources/BaseResource.php     # base API/web resource
  Models/{User,Brand,Talent}.php   # the three login entities
  Policies/BasePolicy.php          # authorization convention
  Providers/AppServiceProvider.php # response macros + strict models
  Services/Service.php             # base service (transactions + fail logging)
  Support/
    ApiResponse.php                # the JSON envelope
    Auth/Guards.php                # multi-guard helper
config/    auth · logging · sanctum · media-library · laravellocalization · scribe · …
resources/
  css/app.css                      # Tailwind v4 entry (dark = class strategy)
  js/http.js                       # shared fetch wrapper (parses the envelope)
  views/…                          # Blade layouts, components, auth
routes/    web.php (locale group + guards) · auth.php · console.php
docs/
  specs/                           # ← canonical: schema-master, talent-spec, brand-spec
  architecture · schema · api · conventions · decisions · changelog
```

## Third-party data

- **Countries and cities** (`resources/data/locations`) — built from [GeoNames](https://www.geonames.org/)
  (`countryInfo.txt` + `cities15000`: every city of 15,000+ people, largest first), licensed **CC BY 4.0**.
  Country names are PHP intl's English names; `App\Support\Locations` reads them.
- **Country flags** (`public/fama-ui/img/flags`) — [flag-icons](https://github.com/lipis/flag-icons) 7.5.0, MIT
  (licence copied beside the SVGs).

## Response envelope

Every JSON response (web-Ajax and API) uses one shape:

```json
{ "success": true, "data": {}, "message": null, "errors": null, "meta": null }
```

Built via `App\Support\ApiResponse` and the `response()->success|error|paginated()` macros. See
[`docs/api.md`](docs/api.md).

## Project laws

- Thin controllers, business logic in services + actions, SOLID throughout.
- All media through the media library; external links stay plain URLs.
- Every interaction is Ajax (no page reloads); lists eager-loaded and paginated.
- **Never** run git write operations. See [`CLAUDE.md`](CLAUDE.md) for the full ruleset.
