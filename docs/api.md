# Fama API — mobile developer handoff

The Fama mobile API is **complete** and stable at **v1**. This page is the readable index; the
machine-readable reference (OpenAPI + Postman) and an interactive explorer are linked below. The detailed
endpoint tables follow (mobile endpoints under [Mobile API — `/api/v1`](#mobile-api--apiv1-phase-4a); the
web-Ajax endpoints are documented afterwards for reference — they share this same envelope).

## Generated reference (import these)

| Artifact | Where | Use |
|---|---|---|
| **OpenAPI 3.0 spec** | [`docs/api/openapi.yaml`](api/openapi.yaml) | import into Swagger UI / generate a typed client |
| **Postman collection** | [`docs/api/collection.json`](api/collection.json) | import into Postman and start calling |
| **Interactive docs (try-it-out)** | `GET /docs` (live app) | explore + send test requests in the browser |
| Live spec / collection | `GET /docs.openapi` · `GET /docs.postman` | same files served by the running app |

Regenerate all of the above with **`composer api-docs`** (runs `scribe:generate` and copies the OpenAPI +
Postman artifacts into `docs/api/`). Re-run it whenever API routes or their `@group` / `@authenticated`
annotations change. The collection currently covers **130 endpoints**.

## Base URL & versioning

```
{APP_URL}/api/v1
```

Everything lives under `/api/v1`. A future breaking revision ships as a sibling `/api/v2` group, so v1
never shifts under an existing client.

## Response envelope (every JSON response)

```json
{ "success": true, "data": <mixed|null>, "message": "<string|null>", "errors": <object|null>, "meta": <object|null> }
```

- `success` — boolean outcome.
- `data` — payload (object, array, or list items).
- `message` — human-readable note (a confirmation or an error summary).
- `errors` — validation/field bag `{ "field": ["message", …] }`; `null` on success.
- `meta` — extra metadata; paginated lists put page info at `meta.pagination` (see below).

Built by `App\Support\ApiResponse`. **Errors use the same envelope** — the central handler
(`bootstrap/app.php`) shapes every failure into it:

| Status | When |
|---|---|
| **400** | bad search query (unknown `filter[...]` / `sort` — the message names the allowed keys) |
| **401** | missing / invalid / revoked token |
| **403** | token lacks the required ability, or a foreign (not-owned) resource |
| **404** | unknown record / unpublished profile |
| **422** | validation failure (`errors` populated), domain-rule violation, or illegal state transition |
| **429** | rate limit tripped (`meta.retry_after` = seconds) |
| **500** | unexpected — generic message; details fail-logged to the `api` log channel |

## Authentication (per entity)

Stateless **Sanctum bearer tokens**. Each of the three entities (talent / brand / admin) has its own
auth routes; a token is **ability-scoped** to its guard name (`talent` / `brand` / `admin`), and admin
tokens additionally carry the admin's granular permissions (e.g. `manage-users`, `manage-settings`).

Flow (identical shape for all three; admin has no public register):

```
POST /api/v1/{talent|brand}/register     # public sign-up  → { token, token_type, abilities, <entity> }   (201)
POST /api/v1/{talent|brand|admin}/login  # credentials     → { token, token_type: "Bearer", abilities, <entity> }
# then send the token on every protected request:
Authorization: Bearer {token}
GET  /api/v1/{talent|brand|admin}/me       # current entity
POST /api/v1/{talent|brand|admin}/refresh  # rotate: revokes the presented token, returns a fresh one
POST /api/v1/{talent|brand|admin}/logout   # revoke the presented token
```

- **Talent / brand**: `register` + `login` are public. Protected routes require `abilities:talent` /
  `abilities:brand`.
- **Admin**: `login` only (no public sign-up). New staff are provisioned by an existing admin holding
  `manage-users` via `POST /api/v1/admin/register`. Protected routes require `abilities:admin` (some
  add a permission, e.g. `abilities:manage-settings`).
- Endpoints open to either party (deal inbox, notifications) use `ability:talent,brand` (any of).

## Pagination

Every **unbounded / growing list** (search, feeds, inboxes, moderation queues, notifications, activity)
is paginated and fills `meta.pagination`:

```json
"meta": { "pagination": { "current_page": 1, "last_page": 5, "per_page": 15, "total": 68, "from": 1, "to": 15 } }
```

Pass `?page=N` to traverse. A `ContractComplianceTest` asserts this block (all six keys) on every list
endpoint. **Intentionally NOT paginated** (returned whole, because the client renders the entire set as
one unit and it is naturally bounded): the reference **lookups** (`/lookups/*`), and a talent/brand's own
**editor-state collections** — profile blocks + block-picker, professions, brand images, social handles.
These still return the standard envelope (with `data` as the full collection).

## Locale

Send `Accept-Language: ar` or `Accept-Language: en` (quality-weighted values like `ar-EG,ar;q=0.9,en;q=0.5`
are honoured; anything unsupported falls back to `en`). `SetApiLocale` negotiates it, echoes the chosen
locale on the **`Content-Language`** response header, and — on public reads — translatable fields
(talent headline/bio, brand & campaign description, profession/block names) resolve to that single locale.
Owner-editing endpoints instead return per-locale maps (`{"en": …, "ar": …}`) so the app can edit both.

## Rate limits

Two named throttles (returning a **429** envelope with `meta.retry_after` on trip):

| Bucket | Limit | Scope | Applies to |
|---|---|---|---|
| `api` | 60 / min | token user, else client IP | the whole `/api/v1` group |
| `auth` | 10 / min | email + IP | the credential endpoints (register / login) |

## Client wrapper (web)
`resources/js/http.js` wraps `fetch` for the web-Ajax layer (CSRF + `Accept: application/json`, envelope
parsing, `ApiError` on failure). It shares this exact envelope, so web and API never drift.

## Generated docs (Scribe)
`knuckleswtf/scribe` (`config/scribe.php`) documents the `api/*` routes — grouped + ordered via `@group`
docblocks, `@authenticated` / `@unauthenticated` per endpoint, bearer auth, "try it out", Postman +
OpenAPI. Regenerate + export with `composer api-docs`.

## Public pages — web endpoints (unguarded)

Locale-prefixed, in `routes/web.php`. GET routes return Blade (public layout); interactive submits use
the JSON envelope + `http.js`. All resolve **published** talents only (404 otherwise).

| Page | Method + path | Purpose |
|---|---|---|
| Talent profile | `GET /{slug}` | header (primary profession leading) + visible blocks in position order; bumps `view_count` via event; eager-loaded |
| Project | `GET /{slug}/work/{project}` | one `projects` record expanded (404 if not the talent's) |
| Review form | `GET /{slug}/review` | public review form |
| Review submit | `POST /{slug}/review` | writes a **pending** review (`is_approved = false`) — envelope |
| Discovery | `GET /discover` | search page shell |
| Discovery search | `GET /discover/search` | paginated talent cards — envelope; filters below |
| Booking CTA | `GET /{slug}/enquire` | booking/enquiry form (services + brief) |
| Enquiry submit | `POST /{slug}/enquire` | writes a `deal_enquiries` row (availability-checked) — envelope |
| Brand profile | `GET /brands/{slug}` | **published** brand header + credibility + approved reviews + public campaigns + social handles; eager-loaded, no N+1 |
| Campaign detail | `GET /brands/{slug}/campaigns/{campaign-slug}` | one **public** campaign (title/description/cover/budget/location/dates + roles sought + gallery); binding scoped to the brand |

**Discovery filters** (spatie/laravel-query-builder, `App\Queries\TalentSearch`), passed as
`filter[...]` query params: `type` & `category` (comma-separated slugs, through the pivot),
`availability`, `city`, `country`, `equipment` (category), `software` (name), `q` (name search).
Sorts: `sort=view_count|created_at` (default `-view_count`). 12 per page. Output: `TalentCardResource`.

The `{slug}` profile route is the single-segment catch-all and stays **last**; `/discover`, the
`/{slug}/...` sub-pages, and the two-segment `/brands/{slug}[/campaigns/{campaign-slug}]` routes are
registered before it. Brand pages resolve a **published** brand only (404 otherwise), campaign detail a
**public** campaign only, and the campaign binding is scoped so it must belong to the brand in the path.

## Talent dashboard — web endpoints (session, `auth:talent`)

Defined in `routes/talent.php` (prefix `/talent`, name `talent.`). GET page routes return a Blade shell;
every other action returns the JSON envelope for the Alpine/http.js front-end (no reloads). Lists are
eager-loaded and paginated (`meta.pagination`). All resources are scoped to the authenticated talent;
touching another talent's resource returns **403**. Domain-rule violations (ineligible block, duplicate
profession) and illegal state transitions (bad publish) return **422** envelopes.

| Area | Method + path | Purpose |
|---|---|---|
| Home | `GET /talent/dashboard` | status overview (draft/live, views, pending reviews, deals slot) |
| Profile | `GET /talent/profile` · `PATCH /talent/profile` | editor shell · update core fields |
| Profile | `POST /talent/profile/hero` | upload hero image (medialibrary) |
| Blocks | `GET /talent/profile/blocks` · `GET /talent/profile/block-picker` | list blocks · eligibility-filtered picker |
| Blocks | `POST /talent/profile/blocks` · `PATCH …/{block}` · `PATCH …/reorder` · `PATCH …/{block}/visibility` · `DELETE …/{block}` | add · fill · drag-reorder · show/hide · remove |
| Professions | `GET/POST /talent/professions` · `PATCH …/{type}/primary` · `PATCH …/reorder` · `DELETE …/{type}` | manage types (seeds blocks) |
| Content | `GET /talent/content/{type}` (+ `/data`, `POST`, `PATCH {id}`, `DELETE {id}`, `PATCH reorder`, `POST {id}/media`) | child-table editors (gallery, digitals, showreel, equipment, projects, software, brand collabs, looks) |
| Rate card | `GET /talent/services` (+ `/data`, `POST`, `PATCH {service}`, `PATCH {service}/toggle`, `DELETE {service}`) | services CRUD + pause/activate |
| Availability | `GET /talent/availability` · `PATCH /talent/availability` | status + travel + rate tier |
| Reviews | `GET /talent/reviews` (+ `/data?status=`, `PATCH {review}/approve`, `PATCH {review}/reject`) | moderation queue |
| Affiliations | `GET /talent/affiliations` (+ `/data`, `POST`, `PATCH {id}`, `PATCH {id}/end`, `DELETE {id}`) | agency representation |
| Press | `GET /talent/press/data` · `POST /talent/press` · `DELETE /talent/press/{press}` | press features |
| Account | `GET /talent/account` · `PATCH /talent/account` · `PATCH /talent/account/publish` | slug/prefs · publish toggle |
| Deals inbox | `GET /talent/deals` · `GET /talent/deals/data?status=` | list, whose-turn, filter, paginated |
| Deal room | `GET /talent/deals/{deal}` · `GET /talent/deals/{deal}/thread` | room shell · header+stepper+timeline payload (marks read) |
| Deal actions | `POST /talent/deals/{deal}/advance` · `/reject` · `/skip` · `/message` | act on the current step / loop back / skip / chat — envelope |

The talent acts as the `talent` role; the current step's `step_type` selects the action shape
(`advance` body: `{fields}` for form, `{note}` for approval, `{attachments}` for upload, `{confirmed}`
for payment, `{signed,signatory}` for contract, `{start_date,end_date}` for schedule, `{body}` for
message, `{}` for info). Acting out of turn → **422**; a foreign deal → **403**. All deal mutations go
through `App\Services\DealService`.

Controllers are thin and delegate to the Phase 1B services (ProfileBlockService, ProfessionsService,
TalentProfileService); validation via Form Requests (`app/Http/Requests/Talent`), output via Resources
(`app/Http/Resources`). Front-end components live in `resources/js/dashboard.js`
(`profileEditor`, `professionsManager`, `crudList`).

## Brand dashboard — web endpoints (session, `auth:brand`)

Defined in `routes/brand.php` (prefix `/brand`, name `brand.`). Same conventions as the talent dashboard:
GET page routes return a Blade shell; every other action returns the JSON envelope; lists are eager-loaded
+ paginated; resources are scoped to the authenticated brand (foreign resource → **403**); domain-rule and
illegal state-transition violations → **422**. An **incomplete brand** (`is_complete = false`) hitting
`GET /brand/dashboard` is redirected into the onboarding wizard.

| Area | Method + path | Purpose |
|---|---|---|
| Onboarding | `GET /brand/onboarding` | 6-step wizard shell (redirects to dashboard once complete) |
| Onboarding | `POST /brand/onboarding/{identity,location,creative-needs,aesthetic,budget}` | persist each step (registered → onboarding) |
| Onboarding | `POST /brand/onboarding/complete` | flip `is_complete` (onboarding → complete), returns redirect |
| Home | `GET /brand/dashboard` | completion status, active deals + whose-turn, recent campaigns, feed entry |
| Profile | `GET /brand/profile` · `PATCH /brand/profile` | editor shell · core fields |
| Profile media | `POST /brand/profile/{logo,cover}` · `PATCH /brand/profile/aesthetic` | logo/cover (medialibrary) · references + mood tags |
| Gallery | `GET /brand/profile/images` · `POST …` · `DELETE …/{image}` | brand images CRUD |
| Social | `GET /brand/social/data` · `POST /brand/social` · `DELETE /brand/social/{handle}` | social handles |
| Creative needs | `GET /brand/creative-needs` · `PATCH /brand/creative-needs` | talent types + project types + frequency + budget tier |
| Campaigns | `GET /brand/campaigns` · `GET …/data` · `POST …` | manager · list (paginated, `deals_count`) · create |
| Campaign | `GET /brand/campaigns/{c}` · `GET …/data` · `PATCH …` · `DELETE …` | workspace · payload (roles, gallery, deals) · edit · delete |
| Campaign lifecycle | `PATCH …/{c}/status` (`{action: open\|start\|complete\|cancel}`) · `PATCH …/{c}/public` · `POST …/{c}/media` | transitions · list ⇄ private · add media |
| Discovery | `GET /brand/discover` · `GET /brand/discover/feed` | feed shell · personalised paginated feed (writes a `view` signal) |
| Discovery actions | `POST /brand/discover/save` · `POST /brand/discover/brief` (`{talent_id}`) | write `save` / `brief_sent` signals |
| Start a deal | `POST /brand/deals` (`{talent_id, service_id?, deal_flow_id?, campaign_id?, brief?}`) | Path A — creates the deal via the engine, returns the room `redirect`; guards → 422 |
| Enquiries | `GET /brand/enquiries` · `GET …/data` · `POST /brand/enquiries/{enquiry}/convert` | Path B — pending enquiries (email-matched) + convert to a deal (403 foreign · 422 handled) |
| Deals inbox | `GET /brand/deals` · `GET /brand/deals/data?status=` | list, `is_brand_turn`, filter, paginated |
| Deal room | `GET /brand/deals/{deal}` · `GET …/thread` | room shell · header+stepper+timeline (marks read) |
| Deal actions | `POST /brand/deals/{deal}/{advance,reject,skip,message}` | act as the `brand` role (submit brief, accept quote, sign, pay) |
| Reviews | `GET /brand/reviews` · `GET /brand/reviews/data` | reviews received (approved only, read-only, 3 sub-ratings) |
| Account | `GET /brand/account` · `PATCH /brand/account` · `PATCH /brand/account/publish` | settings/slug · publish toggle (published ⇄ unpublished) |

The brand acts as the `brand` role on the **shared** deal engine (same `advance` body shapes as the talent
side; `awaiting_brand` is highlighted). Controllers delegate to the Phase 2B services (BrandOnboardingService,
CampaignService, BrandReviewService, BrandSignalService) and the `BrandTalentFeed` query; validation via
Form Requests (`app/Http/Requests/Brand`) + inline rules, output via Resources (`BrandResource`,
`CampaignResource`, `BrandReviewResource`, `TalentCardResource`, shared `DealResource`). Front-end
components live in `resources/js/brand.js`.

## Admin dashboard — web endpoints (session, `auth:admin`)

Defined in `routes/admin.php` (prefix `/admin`, name `admin.`). Same conventions as the other dashboards
(Blade shells + JSON envelope + `http.js`, no reloads, eager-load + paginate). **Page access is gated per
capability by `can:` middleware** (spatie permissions on the admin guard) — a powerless admin gets **403**;
every action also re-authorizes + audits inside the Phase 3A service.

| Area | Method + path | Perm | Purpose |
|---|---|---|---|
| Home | `GET /admin/dashboard` | — | governance overview (pending queues, deals awaiting admin) |
| Flows | `GET /admin/flows` · `GET …/data` · `POST …` | manage-flows | list · paginated · create (draft) |
| Flow | `GET /admin/flows/{f}` · `GET …/data` · `PATCH …` | manage-flows | workspace · payload (steps) · edit meta/scope |
| Flow lifecycle | `PATCH …/{f}/{default,activate,archive}` | manage-flows | set default · activate · archive |
| Flow steps | `POST …/{f}/steps` · `PATCH …/steps/reorder` · `PATCH …/steps/{s}` · `DELETE …/steps/{s}` | manage-flows | add · drag-reorder · edit · remove |
| Professions | `GET /admin/professions` · `GET …/data` · `POST …` · `PATCH …/{type}/blocks` | manage-flows | catalog · list · add profession · edit default_blocks |
| Moderation | `GET /admin/moderation` + `/{talents,reviews,brands,brand-reviews,campaigns}` | moderate-content | queues (paginated JSON) |
| Moderation actions | `PATCH …/{queue}/{id}/{action}` · `POST …/reviews/batch` | moderate-content | suspend/verify/approve/cancel… · batch approve/reject |
| Deal console | `GET /admin/deals` · `GET …/data?status=` · `GET …/{deal}` · `GET …/thread` | intervene-deals | list · filter · console · payload |
| Deal intervene | `POST /admin/deals/{deal}/{override,advance,nudge,reassign,cancel}` | intervene-deals | override stuck step / act as admin / nudge / reassign / cancel |
| Activity | `GET /admin/activity` · `GET …/data?q=&log=` | manage-settings | searchable audit trail |
| Settings | `GET /admin/settings` · `PATCH /admin/settings` | manage-settings | platform globals + feature flags |
| Admin users | `GET /admin/users` · `GET …/data` · `POST …` · `PATCH …/{u}` · `PATCH …/{u}/roles` · `DELETE …/{u}` | manage-users | staff CRUD + role assignment |

Controllers are thin (`app/Http/Controllers/Admin/*`) and delegate every mutation to the Phase 3A admin
services; validation via Form Requests (`app/Http/Requests/Admin`) + inline rules; output via Resources
(`DealFlowResource`, `ActivityResource`, `AdminUserResource`, shared `DealResource`, etc.). Front-end
components live in `resources/js/admin.js`.

## Mobile API — `/api/v1` (Phase 4A)

Defined in `routes/api.php` (registered under the `api` prefix by `bootstrap/app.php`, so every path is
`/api/v1/...`). Stateless Sanctum token auth, the shared JSON envelope, and the same domain services the
web layer uses (controllers stay thin). The cross-cutting conventions — envelope, error statuses,
pagination, locale header, rate limits and the token flow — are in the [handoff header](#authentication-per-entity)
at the top of this page; the tables below are the per-endpoint reference.

### Authentication (per guard)

| Guard | Method + path | Auth | Purpose |
|---|---|---|---|
| Talent | `POST /api/v1/talent/register` | — | public sign-up → talent-scoped token (201) |
| Talent | `POST /api/v1/talent/login` | — | credentials → token |
| Talent | `GET /api/v1/talent/me` · `POST …/refresh` · `POST …/logout` | `abilities:talent` | current talent · rotate token · revoke token |
| Brand | `POST /api/v1/brand/register` | — | public sign-up → brand-scoped token (201) |
| Brand | `POST /api/v1/brand/login` | — | credentials → token |
| Brand | `GET /api/v1/brand/me` · `POST …/refresh` · `POST …/logout` | `abilities:brand` | current brand · rotate · revoke |
| Admin | `POST /api/v1/admin/login` | — | credentials → token (abilities = `admin` + spatie permissions) |
| Admin | `GET /api/v1/admin/me` · `POST …/refresh` · `POST …/logout` | `abilities:admin` | current admin · rotate · revoke |
| Admin | `POST /api/v1/admin/register` | `abilities:manage-users` | **provision** a staff account (no public admin sign-up) |

Admin accounts are never self-registered — an open admin-signup endpoint would be a privilege-escalation
hole. `admin/register` mirrors the web `AdminUserController`: an existing admin holding `manage-users`
creates the account (audited, `admin_users` log), and the new admin logs in themselves.

### Discovery + public profile interactions (public)

| Method + path | Purpose |
|---|---|
| `GET /api/v1/talents` | paginated talent search — reuses `App\Queries\TalentSearch`; `TalentCardResource`. Whitelisted `filter[...]`: type, category, availability, city, country, equipment, software, q. Sorts: view_count, created_at |
| `GET /api/v1/talents/{talent:slug}` | full published talent passport (`Api\V1\TalentResource`: visible blocks + comp card + types + services + approved reviews, locale-resolved); bumps view count; 404 if unpublished |
| `GET /api/v1/talents/{talent:slug}/projects/{project}` | one published talent's case study (scoped to the talent; 404 otherwise) |
| `POST /api/v1/talents/{talent:slug}/reviews` | submit a review → lands pending (`StoreReviewRequest`) |
| `POST /api/v1/talents/{talent:slug}/enquiries` | submit a booking enquiry → `deal_enquiries` (availability-checked; `StoreEnquiryRequest`) |
| `GET /api/v1/brands` | paginated public brand directory — `App\Queries\BrandSearch`; `Api\V1\BrandResource`. Whitelisted `filter[...]`: industry, stage, reach, city, country, verified, q. Sorts: created_at, name |
| `GET /api/v1/brands/{brand:slug}` | published brand profile (`Api\V1\BrandResource`: credibility + aesthetic + social + images + approved reviews + public campaigns, locale-resolved); 404 if unpublished |
| `GET /api/v1/brands/{brand:slug}/campaigns/{campaign:slug}` | one public campaign under a published brand (scope-bound to the brand; 404 otherwise) |

An unknown filter or sort on a search endpoint returns a **400** envelope naming the allowed keys
(spatie/laravel-query-builder — the whitelist is the contract).

### Deals — cross-entity read (authenticated, talent **or** brand token)

| Method + path | Auth | Purpose |
|---|---|---|
| `GET /api/v1/deals` | `ability:talent,brand` | paginated inbox scoped to the token's entity; `DealResource` |
| `GET /api/v1/deals/{deal}` | `ability:talent,brand` | one deal the caller is a party to (**403** otherwise) |

### Talent workspace (Phase 4B — `abilities:talent`)

The full talent management surface under `/api/v1/talent`, thin controllers
(`app/Http/Controllers/Api/V1/Talent`) over the **same services, Form Requests and Resources** the web
dashboard uses. Every list is eager-loaded + paginated (`meta.pagination`); a foreign resource → **403**;
domain-rule / illegal-transition → **422**. Translatable fields are returned as per-locale maps (the owner
edits both languages).

| Area | Method + path | Purpose |
|---|---|---|
| Profile | `GET /talent/profile` · `PATCH /talent/profile` · `POST /talent/profile/hero` | own profile (core + blocks) · update core · hero upload (multipart → URL) |
| Blocks | `GET /talent/profile/blocks` · `GET …/block-picker` · `POST …/blocks` · `PATCH …/blocks/reorder` · `PATCH …/blocks/{block}` · `PATCH …/blocks/{block}/visibility` · `DELETE …/blocks/{block}` | list · eligibility picker · add · reorder · fill · show/hide · remove |
| Professions | `GET /talent/professions` · `POST …` · `PATCH …/reorder` · `PATCH …/{type}/primary` · `DELETE …/{type}` | manage types (adding seeds blocks) |
| Content | `GET/POST /talent/content/{type}` · `PATCH …/{type}/reorder` · `PATCH …/{type}/{id}` · `DELETE …/{type}/{id}` · `POST …/{type}/{id}/media` | child tables (gallery, look_types, digitals, showreel, equipment, projects, software_stack, brand_collabs) via the shared `BlockContentRegistry`; media returns the conversion URL |
| Comp card | `GET /talent/comp-card` · `PUT …` · `DELETE …` | 1:1 model stats (show null / upsert / remove) |
| Services | `GET /talent/services` · `POST …` · `PATCH …/{service}` · `PATCH …/{service}/toggle` · `DELETE …/{service}` | rate card CRUD + pause/activate |
| Availability | `GET /talent/availability` · `PATCH …` | status (state machine) + travel + rate tier |
| Reviews | `GET /talent/reviews?status=` · `PATCH …/{review}/approve` · `PATCH …/{review}/reject` | own moderation queue |
| Affiliations | `GET /talent/affiliations` · `POST …` · `PATCH …/{affiliation}` · `PATCH …/{affiliation}/end` · `DELETE …/{affiliation}` | agency representation |
| Press | `GET /talent/press` · `POST …` · `DELETE …/{press}` | press features |
| Account | `GET /talent/account` · `PATCH …` · `PATCH …/publish` | slug/prefs · publish toggle |
| Deals | `GET /talent/deals?status=` · `GET …/{deal}` · `POST …/{deal}/{advance,reject,skip,message}` | inbox (filter, paginated) · room (steps + messages + whose-turn) · step actions via the engine |
| Enquiries | `GET /talent/enquiries` · `GET …/{enquiry}` | incoming booking enquiries (read-only) |

Step actions (send quote, accept, upload, sign, pay) are all `advance` with the body the current step's
`step_type` expects (`{fields}` / `{note}` / `{attachments}` / `{confirmed}` / …), routed through
`DealService` exactly like the web deal room. The registry (`App\Support\Talent\BlockContentRegistry`) is
the single source of truth for content field sets, validation and serialization — shared by the web
dashboard and this API.

### Brand workspace (Phase 4C — `abilities:brand`)

The full brand management surface under `/api/v1/brand`, thin controllers
(`app/Http/Controllers/Api/V1/Brand`) over the **same services** the web dashboard uses
(`BrandOnboardingService`, `CampaignService`, `BrandSignalService`, `BrandTalentFeed`, `DealService`).
Lists are eager-loaded + paginated; foreign resource → **403**; domain-rule / illegal-transition → **422**.
Translatable `description` is a per-locale map for the owner. Enum option lists come from the shared
`App\Support\Brand\BrandOptions`.

| Area | Method + path | Purpose |
|---|---|---|
| Onboarding | `GET /brand/onboarding` · `POST …/{identity,location,creative-needs,aesthetic,budget,complete}` | resume status · the 6 steps (complete flips `is_complete`) |
| Profile | `GET /brand/profile` · `PATCH …` · `POST …/logo` · `POST …/cover` · `PATCH …/aesthetic` | own profile (core + aesthetic + social + images) · update · logo/cover upload · aesthetic |
| Images | `GET /brand/profile/images` · `POST …` · `DELETE …/{image}` | gallery CRUD (multipart) |
| Social | `GET /brand/social` · `POST …` · `DELETE …/{handle}` | social handles |
| Creative needs | `GET /brand/creative-needs` · `PATCH …` | talent types + project types + frequency + budget tier (drives the feed) |
| Campaigns | `GET /brand/campaigns` · `POST …` · `GET …/{campaign}` · `PATCH …/{campaign}` · `PATCH …/{campaign}/status` · `PATCH …/{campaign}/public` · `POST …/{campaign}/media` · `DELETE …/{campaign}` | CRUD + roles + media + lifecycle; `show` includes the deals under the campaign |
| Discovery | `GET /brand/discover` · `POST …/save` · `POST …/brief` | personalised feed (filter + sort) · save/brief signal writes |
| Deal initiation | `POST /brand/deals` (`{talent_id, service_id?, deal_flow_id?, campaign_id?, brief?}`) | Path A — start a deal (201, `DealResource` + `meta.room`); guards → 422 |
| Enquiries | `GET /brand/enquiries` · `POST /brand/enquiries/{enquiry}/convert` | Path B — pending enquiries (paginated) + convert (403 foreign · 422 handled) |
| Deals | `GET /brand/deals?status=` · `GET …/{deal}` · `POST …/{deal}/{advance,reject,skip,message}` | inbox (filter, paginated) · room · brand-side step actions (brief, accept quote, sign, pay) |
| Reviews | `GET /brand/reviews` | reviews received (approved-only, read-only) |
| Credibility | `GET /brand/credibility` | accrued credibility (read; null until first completed deal) |
| Account | `GET /brand/account` · `PATCH …` · `PATCH …/publish` | settings-stage fields + slug · publish toggle (requires completion) |

The brand acts as the `brand` role on the shared deal engine (same `advance` body shapes as the talent
side). `App\Support\Brand\BrandOptions` is the single source of truth for the brand enum option lists,
shared with the web controllers' validation.

### Notifications (Phase 4D — talent **or** brand token)

Deal turn changes and new deal messages are written to the polymorphic `notifications` table as deals
progress (`DealService` dispatches `App\Notifications\DealTurnChanged` / `NewDealMessage` on the `database`
channel). Delivery is intentionally basic; the payload contract is stable so push/email channels can be
layered on later without changing it.

| Method + path | Auth | Purpose |
|---|---|---|
| `GET /api/v1/notifications` | `ability:talent,brand` | paginated feed, newest first (`Api\V1\NotificationResource`) |
| `GET /api/v1/notifications/unread-count` | `ability:talent,brand` | `{ unread: n }` for a badge |
| `POST /api/v1/notifications/{id}/read` | `ability:talent,brand` | mark one read |
| `POST /api/v1/notifications/read-all` | `ability:talent,brand` | mark all read |

Each notification's `data` carries `type` (`deal.started` / `deal.turn` / `deal.message`), `deal_id`, `deal_reference`,
`deal_title` and a rendered `message`. Notifications are scoped to the token's entity automatically
(Laravel's `Notifiable`).

### Reference / lookups (Phase 4D — public)

Catalog data the app renders dynamic UI from — read-only and unauthenticated (onboarding forms need them
before a token exists). Translatable names come back in the request locale.

| Method + path | Purpose |
|---|---|
| `GET /api/v1/lookups/talent-types` | the profession catalog (`Api\V1\TalentTypeResource`) |
| `GET /api/v1/lookups/block-types` | the active profile-block catalog (`Api\V1\BlockTypeResource`) |
| `GET /api/v1/lookups/deal-flows` | the active deal flows on offer, each with its ordered steps (`DealFlowResource`) |
| `GET /api/v1/lookups/options` | the brand + talent enum option lists (industries, moods, budgets, rate tiers, price units, …) that back the app's selects |

### Admin (lite) (Phase 4D — admin token, policy-gated)

Read-only reads an admin mobile client genuinely needs; heavy admin (flow building, moderation actions,
deal intervention) stays on the web.

| Method + path | Auth | Purpose |
|---|---|---|
| `GET /api/v1/admin/overview` | `abilities:admin` | governance counts (pending queues, deals awaiting admin, catalog totals) |
| `GET /api/v1/admin/activity` | `abilities:manage-settings` | recent audit trail, paginated + filterable (`log`, `q`); `ActivityResource` |

### Locale & media consistency

Every read above runs through `SetApiLocale` (Accept-Language → en/ar, `Content-Language` echoed);
translatable fields resolve to that locale (public reads) or return per-locale maps (owner edits). All
image URLs come from medialibrary accessors, and list endpoints eager-load `media` so they stay N+1-free.
