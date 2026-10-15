# API

> The mobile API is built in Phase 4. This documents the **contract** the whole app already follows so
> web-Ajax and API stay identical, plus how docs are generated.
>
> **Removed features (`docs/decisions.md` ADR-K/L/M):** the rate-card (`/talent/services*`), availability
> & travel (`/talent/availability`), and affiliations/press (`/talent/affiliations*`, `/talent/press*`)
> endpoints were removed, along with the `availability`/`service` filters and the enquiry availability gate.

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
`knuckleswtf/scribe` (`config/scribe.php`) documents the `api/*` routes — live since Phase 5K
(the feed's `/api/v1` mirror is the first real API surface; the rest of the platform API is Phase 4):

```bash
php artisan scribe:generate   # regenerates /docs, the OpenAPI spec and the Postman collection
# outputs: resources/views/scribe + storage/app/private/scribe/{openapi.yaml, collection.json}
```

## Feed — web endpoints (Phase 5; locale-prefixed, Ajax envelope)

All feed routes run `resolve.actor` (guests welcome — ADR-F8); write routes add
`require.actor:<action>` — a guest write returns **401 with `data.intent`** and the intent replays
once after login. Rate limits (per actor, else IP): posts 10/h, comments 10/min, likes + follows
60/min, link previews 20/min, mention search 30/min; throttle answers are friendly 429 envelopes.

| Method | Path | Notes |
|---|---|---|
| GET | `/` | redirect: `/feed` when it has posts, else login (signed-in → dashboard) |
| GET | `/feed` | page shell |
| GET | `/feed/items?cursor=` | ranked stream — `{items, casting_positions, next_cursor, total}` (ADR-F5) |
| GET | `/feed/casting` | 12 discovery talents + `followed_by_viewer` |
| GET | `/posts/{post}` | detail page (unpublished → 404) |
| GET | `/posts/{post}/fragment` | `{html}` envelope fragment |
| GET | `/talents/{username}/posts` | published posts, pinned first, paginated 12 |
| POST | `/posts` | note/media (StorePostRequest; talents only; `media_alt[]` order-matched per-image alt ≤160 → media custom property) |
| PATCH / DELETE | `/posts/{post}` | 15-min edit window / soft delete |
| POST | `/posts/{post}/pin` | max one pinned |
| POST | `/projects/{project}/share` | project_share + snapshot (ADR-F2) |
| GET / POST | `/posts/{post}/comments` | thread (10/page, 3 preview replies, tombstones) / create |
| GET | `/comments/{comment}/replies` | replies, paginated 10 oldest-first |
| PATCH / DELETE | `/comments/{comment}` | edit window / tombstoning delete |
| POST / DELETE | `/posts/{post}/like`, `/comments/{comment}/like` | idempotent |
| POST / DELETE | `/follow/{type}/{id}` | talent→talent, brand→talent |
| GET | `/talents/{username}/followers`, `/following` | paginated 24 + `followed_by_viewer` |
| GET | `/mentions/search?q=` | composer autocomplete, 8 grouped |
| POST | `/links/preview` | instant chip parse + queued enrichment (ADR-F4) |
| POST | `/posts/{post}/report`, `/comments/{comment}/report` | once per target per actor; `reason` ∈ config `feed.report_reasons` (7 keys), `note` required for `other` |
| GET | `/notifications?filter=unread\|mention` · POST `/notifications/read` · GET `/notifications/unread-count` | actor inbox, localized at read |
| GET | `/notifications/all` | the notifications PAGE (fama-ui shell; list loads from `/notifications`) |
| POST / DELETE | `/posts/{post}/bookmark` | save/unsave (5X; private collections, ADR-F9) |
| GET | `/feed/suggestions/talents`, `/feed/suggestions/brands` | rail suggestions, offset cursor (5Z) |
| POST | `/feed/suggestions/dismiss` | permanent dismissal, 60/min (5Z) |

Resources: `PostResource`, `CommentResource`, `ActorResource`, `LinkChipResource`,
`NotificationResource` (`app/Http/Resources/Feed`) — every list row carries the viewer's
interaction state, so the frontend never makes a second call.

## Feed — mobile API v1 (`/api/v1`, Sanctum)

Every feed read/write above is mirrored under `/api/v1` (`routes/api.php`) through the **same
controllers, form requests, actions and Resources**. Reads are public; writes require
`auth:sanctum` (talent/brand tokens; `CurrentActor` resolves the sanctum tokenable). No sessions —
no intent replay; guests simply get the 401 envelope. 34 routes.

**Endpoint count: 236** — 166 pre-feed web endpoints (incl. `GET /talent/dashboard/views`,
`PATCH /talent/profile/sections/universal-position`, the 8 Projects endpoints (ADR-X), the 5 speciality
endpoints (ADR-Y), the 12 portfolio-section endpoints (ADR-Z) and the 9 project endpoints added by
ADR-AA: 3 × media, 3 × owner-side credits, 3 × credited-person side. Discovery's `filter[...]` params
are query params on the existing endpoint, never endpoints of their own) + 36 feed web endpoints
(31 + saves + the suggestion rails) + 34 API v1 mirrors.

## Public pages — web endpoints (unguarded)

Locale-prefixed, in `routes/web.php`. GET routes return Blade (public layout); interactive submits use
the JSON envelope + `http.js`. All resolve **published** talents only (404 otherwise).

| Page | Method + path | Purpose |
|---|---|---|
| Talent profile | `GET /{slug}` (+ `?skill=`) | two regions (ADR-R): identity + universal blocks, then skill tabs. The active (primary or `?skill=`) tab renders server-side; bumps `view_count` via event; eager-loaded |
| Skill tab (lazy) | `GET /{slug}/tab/{skill}` | one skill's rendered blocks as the envelope `{ html }` (no `view_count` bump); 404 if the skill has no visible blocks / unknown / unpublished |
| Project | `GET /{slug}/work/{project}` | one `projects` record expanded (404 if not the talent's) |
| Review form | `GET /{slug}/review` | public review form |
| Review submit | `POST /{slug}/review` | writes a **pending** review (`is_approved = false`) — envelope |
| Discovery | `GET /discover` | search page shell |
| Discovery search | `GET /discover/search` | paginated talent cards — envelope; filters below |
| Recognition form | `GET /{slug}/recognise` | write a peer vouch (ADR-Z). **Talent guard** — a guest is redirected to the talent login with this as the intended URL; own profile redirects back with a notice |
| Recognition submit | `POST /{slug}/recognise` | talent → talent vouch; lands pending for the admin queue. A peer vouches ONCE — re-submitting replaces and re-moderates — envelope |
| Booking CTA | `GET /{slug}/enquire` | booking/enquiry form (brief) |
| Enquiry submit | `POST /{slug}/enquire` | writes a `contract_enquiries` row (always allowed) — envelope |

**Discovery filters** (spatie/laravel-query-builder, `App\Queries\TalentSearch`), passed as
`filter[...]` query params: `type` & `category` (comma-separated slugs, through the pivot),
`city`, `country`, `equipment` (category, crew scope), `software` (name, creative scope),
`looks` (`look_types.name` English path, model scope — indexed), `speciality` (comma-separated
speciality **slugs**, ADR-Y), `available` / `will_travel` (booleans — the Now strip, ADR-Z),
`open_to` (skill slugs of a stated direction — surfaces talents who WANT the work but have not done it
yet), `q` (name search). Speciality slugs are unique per skill, **not**
globally — `filter[speciality]=fashion` deliberately matches fashion photographers, models *and*
stylists, and combining it with `filter[type]` is how you narrow. Retired specialities still match, so
nobody drops out of search when the catalog moves on.
The UI is skills-first with the non-skill filters in an "Advanced filters" modal scoped by category
(talent-spec). *(No mobile API doc regeneration — the mobile API is Phase 4 and there is no
`composer api-docs` script yet; this contract doc is updated by hand.)*
Sorts: `sort=view_count|created_at` (default `-view_count`). 12 per page. Output: `TalentCardResource`.

The `{slug}` profile route is the single-segment catch-all and stays **last**; `/discover`, the
`/{slug}/...` sub-pages, and the two-segment `/brands/{slug}[/projects/{project-slug}]` routes are
registered before it. Brand pages resolve a **published** brand only (404 otherwise), project detail a
**public** project only, and the project binding is scoped so it must belong to the brand in the path.

## Talent dashboard — web endpoints (session, `auth:talent`)

Defined in `routes/talent.php` (prefix `/talent`, name `talent.`). GET page routes return a Blade shell;
every other action returns the JSON envelope for the Alpine/http.js front-end (no reloads). Lists are
eager-loaded and paginated (`meta.pagination`). All resources are scoped to the authenticated talent;
touching another talent's resource returns **403**. Domain-rule violations (ineligible block, duplicate
skill, partial pricing rate) and illegal state transitions (bad publish) return **422** envelopes.

The **Profile editor** is the single profile surface (sidebar: Home · Profile · Content · Reviews ·
Contracts). The old standalone Professions + Account tabs are folded into it (ADR-N): Skills, Username (the
`slug`), Publish, and the Pricing rate all live under `/talent/profile`.

| Area | Method + path | Purpose |
|---|---|---|
| Home | `GET /talent/dashboard` | status overview (visibility switch, views KPI + weekly delta, contracts slot) |
| Home | `GET /talent/dashboard/views?range=7d\|30d\|90d\|all` | bucketed view series for the KPI chart ('all' = since profile creation; bad range → 422) |
| Profile | `GET /talent/profile` · `PATCH /talent/profile` | editor shell · update core fields (incl. `slug`, shown as **Username**) |
| Pricing rate | `PATCH /talent/profile/pricing` | set/clear the indicative rate (`rate_unit`/`rate_amount`/`rate_currency`; all-or-nothing) |
| Publish | `PATCH /talent/profile/publish` | publish/unpublish toggle (`{publish: bool}`) |
| Sections | `PATCH /talent/profile/sections/universal-position` | universal section's slot among the skills (`{position: int}`; 0 = above the tabs, else below) |
| Blocks | `GET /talent/profile/blocks` · `GET /talent/profile/block-picker?talent_type_id=` | list blocks (each carries `talent_type_id`) · **per-scope** eligibility picker (ADR-Q) |
| Blocks | `POST /talent/profile/blocks` (`{block_type_id, talent_type_id?}`) · `PATCH …/{block}` · `PATCH …/reorder` (`{talent_type_id?, order}`) · `PATCH …/{block}/move` (`{talent_type_id?}`) · `PATCH …/{block}/visibility` · `DELETE …/{block}` | add-to-scope · fill (`content`/`settings`/`layout` only — block names are admin-governed, `title` is ignored) · reorder-within-scope · **move between scopes** · show/hide · remove |
| Skills | `GET /talent/profile/skills` · `POST …` · `PATCH …/{type}/primary` · `PATCH …/reorder` · `DELETE …/{type}` | manage skills (seeds blocks). The GET payload also carries `specialities` keyed by skill id: `{options[], selected[]}` |
| Now | `PATCH /talent/profile/now` | the two time-sensitive toggles (`will_travel`, `is_available`; both optional so one can flip without restating the other). `availability_changed_at` is stamped ONLY when availability actually flips — response carries the stamp + a humanised form (ADR-Z) |
| Links | `PUT /talent/profile/social` | replace the social handles as ONE ordered list (`{handles: [{platform, handle, url}]}`, present-but-empty clears them; one row per platform, 422 on a duplicate) |
| The work | `POST /talent/projects/{project}/media` · `PATCH …/media/reorder` · `DELETE …/media/{media}` | the project's ordered gallery (ADR-AA) — images + video, max 12 per upload, 50 MB each. Uploads APPEND; the **order is the edit** (first item = the lead frame) |
| Credits (owner) | `POST /talent/projects/{project}/credits` · `PATCH …/credits/reorder` · `DELETE …/credits/{credit}` | collaborator credits (ADR-AA). Exactly one of `talent_id` (on Fama → lands **pending**, notifies them, invisible until they accept) or `name` (not on Fama → **listed** immediately, display-only). `talent_type_id` + `role` required |
| Credits (you) | `GET /talent/credits` · `GET …/data` · `PATCH /talent/credits/{credit}/{accept\|decline}` | confirm, refuse or withdraw a credit naming YOU. Authorised on the credit's `talent_id` — the project owner can never answer on your behalf (403) |
| Willing to explore | `GET /talent/profile/interests` · `POST …` · `PATCH …/{interest}` · `DELETE …/{interest}` · `PATCH …/reorder` | directions the talent wants to move toward (ADR-Z). `talent_type_id` required and **need not be a skill they hold**; `speciality_id` optional but must belong to that skill (422); capped at 6 |
| Specialities | `PUT /talent/profile/skills/{type}/specialities` | replace the picks under ONE skill (`{speciality_ids: []}`, present-but-empty clears them). Every id must belong to that skill and still be selectable, else 422 (ADR-Y) |
| Content | `GET /talent/content/{section?}` — page; `{section}` = `universal` or a skill slug (a legacy `{type}` key resolves to the section showing it; `projects` **redirects** to the Projects page). CRUD stays per type: `GET /talent/content/{type}/data`, `POST`, `PATCH {id}`, `DELETE {id}`, `PATCH reorder`, `POST {id}/media` | one section at a time, every content block in it stacked (gallery, digitals, showreel, equipment, software, brand collabs, looks + the read-only reviews list); a `projects` block renders a signpost panel, not an editor (ADR-X) |
| Projects | `GET /talent/projects` · `GET /talent/projects/data?page=&skill=<id>\|none` | page shell (skills + total) · paginated 12 with `meta.counts.{all,none,by_skill}`; `skill` filters to one tab (home column OR tag) or to the untagged leftovers |
| Projects | `POST /talent/projects` · `PATCH /talent/projects/{project}` · `DELETE /talent/projects/{project}` | create/update/remove — **`skill_ids` is required, min 1**, each one of the talent's own skills (ADR-X); tag 0 becomes the home tab. `occurred_on` is the date — **recorded, never rendered** (ADR-AA); `year` is a derived projection of it and is no longer accepted from a client. `speciality_ids` is optional and each id must hang off one of the tagged skills (ADR-Y) — on update, omitting it leaves the picks alone while changing `skill_ids` prunes the ones that lost their skill. `title.en` required; `summary`/`body` are rich text sanitized server-side; `results` is an ordered `[{label,value,compare}]` list (**max 4**, ADR-AB) |
| Projects | `GET /talent/projects/new` · `GET /talent/projects/{project}/edit` | the project editor PAGE (any owner; 403 otherwise) |
| Projects | `POST /talent/projects/{project}/leave` | leave a project you own (co-owner → credit declined; creator → transfer to the earliest-confirmed co-owner; sole owner → 422). `DELETE` is refused (422) while co-owners exist (ADR-AB) |
| Projects | `PATCH /talent/projects/reorder` (`{order: [id,…]}`) | the talent's own display order (drives the public profile); a foreign id → 422 |
| Projects | `POST /talent/projects/{project}/cover` (`cover` file) · `DELETE /talent/projects/{project}/cover` | single-file `cover` collection via medialibrary (jpg/png/webp, ≤10 MB) |
| Reviews | `GET /talent/reviews` (+ `/data?status=`, `PATCH {review}/approve`, `PATCH {review}/reject`) | moderation queue |
| Contracts inbox | `GET /talent/contracts` · `GET /talent/contracts/data?status=` | list, whose-turn, filter, paginated |
| Contract room | `GET /talent/contracts/{contract}` · `GET /talent/contracts/{contract}/thread` | room shell · header+stepper+timeline payload (marks read) |
| Contract actions | `POST /talent/contracts/{contract}/advance` · `/reject` · `/skip` · `/message` | act on the current step / loop back / skip / chat — envelope |
| Feed collections (5X) | `GET /talent/feed/saved` · `GET /talent/feed/liked` · `GET /talent/feed/collection/data?mode=&skill=&q=&page=` | private SAVED / LIKED shells + Ajax rows (ADR-F9; owner-scoped) |
| Follow management (5-follow) | `GET /talent/feed/follows?tab=following\|followers` · `GET /talent/feed/follows/data?tab=&q=&type=&skill=&sort=&page=` | FOLLOWING / FOLLOWERS shells + Ajax rows; mixed-type cards + mutual/quiet-since signals; unfollow/follow-back reuse `/follow/{type}/{id}` |

The talent acts as the `talent` role; the current step's `step_type` selects the action shape
(`advance` body: `{fields}` for form, `{note}` for approval, `{attachments}` for upload, `{confirmed}`
for payment, `{signed,signatory}` for contract, `{start_date,end_date}` for schedule, `{body}` for
message, `{}` for info). Acting out of turn → **422**; a foreign contract → **403**. All contract mutations go
through `App\Services\ContractService`.

Controllers are thin and delegate to the Phase 1B services (ProfileBlockService, SkillsService,
TalentProfileService); validation via Form Requests (`app/Http/Requests/Talent`), output via Resources
(`app/Http/Resources`). Front-end components live in `resources/js/dashboard.js`
(`profileEditor` — which now also holds Skills, pricing and publish — and `crudList`).

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
| Home | `GET /brand/dashboard` | completion status, active contracts + whose-turn, recent projects, feed entry |
| Profile | `GET /brand/profile` · `PATCH /brand/profile` | editor shell · core fields |
| Profile media | `POST /brand/profile/{logo,cover}` · `PATCH /brand/profile/aesthetic` | logo/cover (medialibrary) · references + mood tags |
| Gallery | `GET /brand/profile/images` · `POST …` · `DELETE …/{image}` | brand images CRUD |
| Social | `GET /brand/social/data` · `POST /brand/social` · `DELETE /brand/social/{handle}` | social handles |
| Creative needs | `GET /brand/creative-needs` · `PATCH /brand/creative-needs` | talent types + project types + frequency + budget tier |
| Projects | `GET /brand/projects` · `GET …/data` · `POST …` | manager · list (paginated, `contracts_count`) · create |
| Project | `GET /brand/projects/{c}` · `GET …/data` · `PATCH …` · `DELETE …` | workspace · payload (roles, gallery, contracts) · edit · delete |
| Project lifecycle | `PATCH …/{c}/status` (`{action: open\|start\|complete\|cancel}`) · `PATCH …/{c}/public` · `POST …/{c}/media` | transitions · list ⇄ private · add media |
| Discovery | `GET /brand/discover` · `GET /brand/discover/feed` | feed shell · personalised paginated feed (writes a `view` signal) |
| Discovery actions | `POST /brand/discover/save` · `POST /brand/discover/brief` (`{talent_id}`) | write `save` / `brief_sent` signals |
| Contracts inbox | `GET /brand/contracts` · `GET /brand/contracts/data?status=` | list, `is_brand_turn`, filter, paginated |
| Contract room | `GET /brand/contracts/{contract}` · `GET …/thread` | room shell · header+stepper+timeline (marks read) |
| Contract actions | `POST /brand/contracts/{contract}/{advance,reject,skip,message}` | act as the `brand` role (submit brief, accept quote, sign, pay) |
| Reviews | `GET /brand/reviews` · `GET /brand/reviews/data` | reviews received (approved only, read-only, 3 sub-ratings) |
| Account | `GET /brand/account` · `PATCH /brand/account` · `PATCH /brand/account/publish` | settings/slug · publish toggle (published ⇄ unpublished) |
| Feed collection (5X) | `GET /brand/feed/saved` · `GET /brand/feed/saved/data?skill=&q=&page=` | private SAVED shell + Ajax rows (ADR-F9; owner-scoped) |
| Follow management (5-follow) | `GET /brand/feed/follows?tab=following\|followers` · `GET /brand/feed/follows/data?tab=&q=&skill=&sort=&page=` | FOLLOWING / FOLLOWERS shells + Ajax rows (type filter hidden — brands follow only talents) |

The brand acts as the `brand` role on the **shared** contract engine (same `advance` body shapes as the talent
side; `awaiting_brand` is highlighted). Controllers delegate to the Phase 2B services (BrandOnboardingService,
BrandProjectService, BrandReviewService, BrandSignalService) and the `BrandTalentFeed` query; validation via
Form Requests (`app/Http/Requests/Brand`) + inline rules, output via Resources (`BrandResource`,
`BrandProjectResource`, `BrandReviewResource`, `TalentCardResource`, shared `ContractResource`). Front-end
components live in `resources/js/brand.js`.

## Admin dashboard — web endpoints (session, `auth:admin`)

Defined in `routes/admin.php` (prefix `/admin`, name `admin.`). Same conventions as the other dashboards
(Blade shells + JSON envelope + `http.js`, no reloads, eager-load + paginate). **Page access is gated per
capability by `can:` middleware** (spatie permissions on the admin guard) — a powerless admin gets **403**;
every action also re-authorizes + audits inside the Phase 3A service.

| Area | Method + path | Perm | Purpose |
|---|---|---|---|
| Home | `GET /admin/dashboard` | — | governance overview (pending queues, contracts awaiting admin) |
| Flows | `GET /admin/flows` · `GET …/data` · `POST …` | manage-flows | list · paginated · create (draft) |
| Flow | `GET /admin/flows/{f}` · `GET …/data` · `PATCH …` | manage-flows | workspace · payload (steps) · edit meta/scope |
| Flow lifecycle | `PATCH …/{f}/{default,activate,archive}` | manage-flows | set default · activate · archive |
| Flow steps | `POST …/{f}/steps` · `PATCH …/steps/reorder` · `PATCH …/steps/{s}` · `DELETE …/steps/{s}` | manage-flows | add · drag-reorder · edit · remove |
| Contract templates | `GET /admin/contract-templates` · `GET …/data` · `POST …` | manage-flows | builder list · paginated · create |
| Contract template | `GET …/{t}` · `GET …/data` · `PATCH …` · `DELETE …` | manage-flows | builder · payload · save terms+clauses · delete |
| Template lifecycle | `PATCH …/{t}/default` · `PATCH …/{t}/toggle` · `POST …/preview` | manage-flows | set default · activate/deactivate · live document preview `{html}` |
| Skills | `GET /admin/skills` · `GET …/data` · `POST …` · `PATCH …/{type}/blocks` | manage-flows | catalog · list (each type carries its `specialities`) · add skill · edit default_blocks |
| Specialities | `POST /admin/skills/{type}/specialities` · `PATCH …/{type}/specialities/reorder` · `PATCH /admin/specialities/{speciality}` · `PATCH …/{speciality}/active` | manage-flows | the controlled vocabulary (ADR-Y): add · reorder · edit copy/regional flag (**slug is locked**) · retire/reinstate (grandfathers existing claims — never a delete) |
| Moderation | `GET /admin/moderation` + `/{talents,reviews,brands,brand-reviews,projects}` | moderate-content | queues (paginated JSON) |
| Moderation actions | `PATCH …/{queue}/{id}/{action}` · `POST …/reviews/batch` | moderate-content | talents+brands `verify` (go-live gate, one-way) / suspend / publish / unpublish / delete… · batch approve/reject |
| Delete review + notice | `POST /admin/moderation/reviews/{review}/delete` | moderate-content | soft-delete a review; emails the recipient a reason notice (`email`, `subject`, `reason`) |
| Contract console | `GET /admin/contracts` · `GET …/data?status=` · `GET …/{contract}` · `GET …/thread` | intervene-contracts | list · filter · console · payload |
| Contract intervene | `POST /admin/contracts/{contract}/{override,advance,nudge,reassign,cancel}` | intervene-contracts | override stuck step / act as admin / nudge / reassign / cancel |
| Activity | `GET /admin/activity` · `GET …/data?q=&log=` | manage-settings | searchable audit trail |
| Settings | `GET /admin/settings` · `PATCH /admin/settings` | manage-settings | platform globals + feature flags |
| Admin users | `GET /admin/users` · `GET …/data` · `POST …` · `PATCH …/{u}` · `PATCH …/{u}/roles` · `DELETE …/{u}` | manage-users | staff CRUD + role assignment |

Controllers are thin (`app/Http/Controllers/Admin/*`) and delegate every mutation to the Phase 3A admin
services; validation via Form Requests (`app/Http/Requests/Admin`) + inline rules; output via Resources
(`ContractFlowResource`, `ActivityResource`, `AdminUserResource`, shared `ContractResource`, etc.). Front-end
components live in `resources/js/admin.js`.

## Mobile API endpoints
None wired yet — the Sanctum token API lands in Phase 4; each endpoint will document its request/response
against the envelope above.

**Public-profile shape (contract, ADR-R).** `App\Http\Resources\PublicProfileResource` already defines
what the Phase-4 public-profile endpoint returns (and mirrors the web page's two regions):

```jsonc
{
  "identity": {
    "slug": "layla", "display_name": "Layla Hassan", "headline": {…}, "bio": {…},
    "avatar_url": "…", "base_city": "Cairo", "base_country": "Egypt",
    "view_count": 3765, "projects_count": 2, "rating": 4.7,
    "pricing_rate": { "unit": "day", "amount": "8000.00", "currency": "EGP" },  // null when unset
    "primary_skill": "modeling",
    "skills": [ { "id": 1, "slug": "modeling", "name": {…}, "category": "model", "is_primary": true,
                  "specialities": [ /* SpecialityResource — the talent's claims under this skill */ ] }, … ],
    "now": { "is_available": true, "will_travel": true, "availability_changed_at": "…" },  // ADR-Z
    "worldview": {…},                                    // translatable; taste, not track record
    "social": [ { "platform": "instagram", "handle": "…", "url": "…" }, … ]
  },
  "recognitions": [ /* RecognitionResource — APPROVED peer vouches only (ADR-Z) */ ],
  "interests": [ /* TalentInterestResource — willing to explore (ADR-Z) */ ],
  "universal_blocks": [ /* ProfileBlockResource — talent_type_id = null */ ],
  "skills": [ { "id": 1, "slug": "modeling", "name": {…},
                "specialities": [ /* SpecialityResource (ADR-Y) */ ],
                "blocks": [ /* that skill's blocks */ ] }, … ]
}
```

The web page consumes the same regions server-side; lazy tabs use `GET /{slug}/tab/{skill}` (HTML
fragment envelope) above.
