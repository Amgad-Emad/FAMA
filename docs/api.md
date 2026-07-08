# API

> The mobile API is built in Phase 4. This documents the **contract** the whole app already follows so
> web-Ajax and API stay identical, plus how docs are generated.

## Response envelope (all JSON responses)

```json
{
  "success": true,
  "data": <mixed|null>,
  "message": "<string|null>",
  "errors": <object|null>,
  "meta": <object|null>
}
```

- `success` — boolean outcome.
- `data` — payload (object, array, or list items).
- `message` — human-readable note (e.g. a flash message or error summary).
- `errors` — validation/field error bag: `{ "field": ["message", ...] }`. `null` on success.
- `meta` — extra metadata. Paginated lists populate `meta.pagination`:

```json
"meta": { "pagination": { "current_page": 1, "last_page": 5, "per_page": 15, "total": 68, "from": 1, "to": 15 } }
```

Built by `App\Support\ApiResponse` and the `response()->success|error|paginated()` macros. Validation
and authentication exceptions are auto-rendered into this envelope for JSON/Ajax requests
(`bootstrap/app.php`).

## Auth
- **Web:** session guards (`admin` / `brand` / `talent`) via Breeze.
- **Mobile API (Phase 4):** Sanctum bearer tokens. All three models use `HasApiTokens`; the
  `personal_access_tokens` table is migrated. `config/sanctum.php` `guard` falls back to the three
  session guards for stateful (SPA) requests.

## Client wrapper
`resources/js/http.js` (`window.fama` / ES exports) wraps `fetch`: attaches CSRF +
`X-Requested-With: XMLHttpRequest` + `Accept: application/json`, parses the envelope, and throws
`ApiError { status, message, errors, data, meta }` on failure so callers surface validation errors
inline.

## Generated docs (Scribe)
`knuckleswtf/scribe` is installed (`config/scribe.php`) and will produce OpenAPI + a Postman collection
for the mobile developer once API routes exist:

```bash
php artisan scribe:generate   # outputs the /docs UI, OpenAPI spec, and Postman collection
```

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

## Mobile API endpoints
None yet — the Sanctum token API lands in Phase 4; each endpoint will document its request/response
against the envelope above.
