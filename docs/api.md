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
| Talent profile | `GET /{slug}` (+ `?skill=`) | two regions (ADR-R): identity + universal blocks, then skill tabs. The active (primary or `?skill=`) tab renders server-side; bumps `view_count` via event; eager-loaded |
| Skill tab (lazy) | `GET /{slug}/tab/{skill}` | one skill's rendered blocks as the envelope `{ html }` (no `view_count` bump); 404 if the skill has no visible blocks / unknown / unpublished |
| Project | `GET /{slug}/work/{project}` | one `projects` record expanded (404 if not the talent's) |
| Review form | `GET /{slug}/review` | public review form |
| Review submit | `POST /{slug}/review` | writes a **pending** review (`is_approved = false`) — envelope |
| Discovery | `GET /discover` | search page shell |
| Discovery search | `GET /discover/search` | paginated talent cards — envelope; filters below |
| Booking CTA | `GET /{slug}/enquire` | booking/enquiry form (brief) |
| Enquiry submit | `POST /{slug}/enquire` | writes a `contract_enquiries` row (always allowed) — envelope |

**Discovery filters** (spatie/laravel-query-builder, `App\Queries\TalentSearch`), passed as
`filter[...]` query params: `type` & `category` (comma-separated slugs, through the pivot),
`city`, `country`, `equipment` (category, crew scope), `software` (name, creative scope),
`looks` (`look_types.name` English path, model scope — indexed), `q` (name search).
The UI is skills-first with the non-skill filters in an "Advanced filters" modal scoped by category
(talent-spec). *(No mobile API doc regeneration — the mobile API is Phase 4 and there is no
`composer api-docs` script yet; this contract doc is updated by hand.)*
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
skill, partial pricing rate) and illegal state transitions (bad publish) return **422** envelopes.

The **Profile editor** is the single profile surface (sidebar: Home · Profile · Content · Reviews ·
Contracts). The old standalone Professions + Account tabs are folded into it (ADR-N): Skills, Username (the
`slug`), Publish, and the Pricing rate all live under `/talent/profile`.

| Area | Method + path | Purpose |
|---|---|---|
| Home | `GET /talent/dashboard` | status overview (draft/live, views, pending reviews, contracts slot) |
| Profile | `GET /talent/profile` · `PATCH /talent/profile` | editor shell · update core fields (incl. `slug`, shown as **Username**) |
| Pricing rate | `PATCH /talent/profile/pricing` | set/clear the indicative rate (`rate_unit`/`rate_amount`/`rate_currency`; all-or-nothing) |
| Publish | `PATCH /talent/profile/publish` | publish/unpublish toggle (`{publish: bool}`) |
| Blocks | `GET /talent/profile/blocks` · `GET /talent/profile/block-picker?talent_type_id=` | list blocks (each carries `talent_type_id`) · **per-scope** eligibility picker (ADR-Q) |
| Blocks | `POST /talent/profile/blocks` (`{block_type_id, talent_type_id?}`) · `PATCH …/{block}` · `PATCH …/reorder` (`{talent_type_id?, order}`) · `PATCH …/{block}/move` (`{talent_type_id?}`) · `PATCH …/{block}/visibility` · `DELETE …/{block}` | add-to-scope · fill · reorder-within-scope · **move between scopes** · show/hide · remove |
| Skills | `GET /talent/profile/skills` · `POST …` · `PATCH …/{type}/primary` · `PATCH …/reorder` · `DELETE …/{type}` | manage skills (seeds blocks) |
| Content | `GET /talent/content/{type}` (+ `/data`, `POST`, `PATCH {id}`, `DELETE {id}`, `PATCH reorder`, `POST {id}/media`) | child-table editors (gallery, digitals, showreel, equipment, projects, software, brand collabs, looks) |
| Reviews | `GET /talent/reviews` (+ `/data?status=`, `PATCH {review}/approve`, `PATCH {review}/reject`) | moderation queue |
| Contracts inbox | `GET /talent/contracts` · `GET /talent/contracts/data?status=` | list, whose-turn, filter, paginated |
| Contract room | `GET /talent/contracts/{contract}` · `GET /talent/contracts/{contract}/thread` | room shell · header+stepper+timeline payload (marks read) |
| Contract actions | `POST /talent/contracts/{contract}/advance` · `/reject` · `/skip` · `/message` | act on the current step / loop back / skip / chat — envelope |

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
| Home | `GET /brand/dashboard` | completion status, active contracts + whose-turn, recent campaigns, feed entry |
| Profile | `GET /brand/profile` · `PATCH /brand/profile` | editor shell · core fields |
| Profile media | `POST /brand/profile/{logo,cover}` · `PATCH /brand/profile/aesthetic` | logo/cover (medialibrary) · references + mood tags |
| Gallery | `GET /brand/profile/images` · `POST …` · `DELETE …/{image}` | brand images CRUD |
| Social | `GET /brand/social/data` · `POST /brand/social` · `DELETE /brand/social/{handle}` | social handles |
| Creative needs | `GET /brand/creative-needs` · `PATCH /brand/creative-needs` | talent types + project types + frequency + budget tier |
| Campaigns | `GET /brand/campaigns` · `GET …/data` · `POST …` | manager · list (paginated, `contracts_count`) · create |
| Campaign | `GET /brand/campaigns/{c}` · `GET …/data` · `PATCH …` · `DELETE …` | workspace · payload (roles, gallery, contracts) · edit · delete |
| Campaign lifecycle | `PATCH …/{c}/status` (`{action: open\|start\|complete\|cancel}`) · `PATCH …/{c}/public` · `POST …/{c}/media` | transitions · list ⇄ private · add media |
| Discovery | `GET /brand/discover` · `GET /brand/discover/feed` | feed shell · personalised paginated feed (writes a `view` signal) |
| Discovery actions | `POST /brand/discover/save` · `POST /brand/discover/brief` (`{talent_id}`) | write `save` / `brief_sent` signals |
| Contracts inbox | `GET /brand/contracts` · `GET /brand/contracts/data?status=` | list, `is_brand_turn`, filter, paginated |
| Contract room | `GET /brand/contracts/{contract}` · `GET …/thread` | room shell · header+stepper+timeline (marks read) |
| Contract actions | `POST /brand/contracts/{contract}/{advance,reject,skip,message}` | act as the `brand` role (submit brief, accept quote, sign, pay) |
| Reviews | `GET /brand/reviews` · `GET /brand/reviews/data` | reviews received (approved only, read-only, 3 sub-ratings) |
| Account | `GET /brand/account` · `PATCH /brand/account` · `PATCH /brand/account/publish` | settings/slug · publish toggle (published ⇄ unpublished) |

The brand acts as the `brand` role on the **shared** contract engine (same `advance` body shapes as the talent
side; `awaiting_brand` is highlighted). Controllers delegate to the Phase 2B services (BrandOnboardingService,
CampaignService, BrandReviewService, BrandSignalService) and the `BrandTalentFeed` query; validation via
Form Requests (`app/Http/Requests/Brand`) + inline rules, output via Resources (`BrandResource`,
`CampaignResource`, `BrandReviewResource`, `TalentCardResource`, shared `ContractResource`). Front-end
components live in `resources/js/brand.js`.

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
    "skills": [ { "id": 1, "slug": "modeling", "name": {…}, "category": "model", "is_primary": true }, … ]
  },
  "universal_blocks": [ /* ProfileBlockResource — talent_type_id = null */ ],
  "skills": [ { "id": 1, "slug": "modeling", "name": {…}, "blocks": [ /* that skill's blocks */ ] }, … ]
}
```

The web page consumes the same regions server-side; lazy tabs use `GET /{slug}/tab/{skill}` (HTML
fragment envelope) above.
