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
| Case study | `GET /{slug}/work/{caseStudy}` | one `case_studies` record expanded (404 if not the talent's) |
| Review form | `GET /{slug}/review` | public review form |
| Review submit | `POST /{slug}/review` | writes a **pending** review (`is_approved = false`) — envelope |
| Discovery | `GET /discover` | search page shell |
| Discovery search | `GET /discover/search` | paginated talent cards — envelope; filters below |

**Discovery filters** (spatie/laravel-query-builder, `App\Queries\TalentSearch`), passed as
`filter[...]` query params: `type` & `category` (comma-separated slugs, through the pivot),
`availability`, `city`, `country`, `equipment` (category), `software` (name), `q` (name search).
Sorts: `sort=view_count|created_at` (default `-view_count`). 12 per page. Output: `TalentCardResource`.

The `{slug}` profile route is the single-segment catch-all and stays **last**; `/discover` and the
`/{slug}/...` sub-pages are registered before it.

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
| Content | `GET /talent/content/{type}` (+ `/data`, `POST`, `PATCH {id}`, `DELETE {id}`, `PATCH reorder`, `POST {id}/media`) | child-table editors (gallery, digitals, showreel, equipment, case studies, software, brand collabs, looks) |
| Rate card | `GET /talent/services` (+ `/data`, `POST`, `PATCH {service}`, `PATCH {service}/toggle`, `DELETE {service}`) | services CRUD + pause/activate |
| Availability | `GET /talent/availability` · `PATCH /talent/availability` | status + travel + rate tier |
| Reviews | `GET /talent/reviews` (+ `/data?status=`, `PATCH {review}/approve`, `PATCH {review}/reject`) | moderation queue |
| Affiliations | `GET /talent/affiliations` (+ `/data`, `POST`, `PATCH {id}`, `PATCH {id}/end`, `DELETE {id}`) | agency representation |
| Press | `GET /talent/press/data` · `POST /talent/press` · `DELETE /talent/press/{press}` | press features |
| Account | `GET /talent/account` · `PATCH /talent/account` · `PATCH /talent/account/publish` | slug/prefs · publish toggle |

Controllers are thin and delegate to the Phase 1B services (ProfileBlockService, ProfessionsService,
TalentProfileService); validation via Form Requests (`app/Http/Requests/Talent`), output via Resources
(`app/Http/Resources`). Front-end components live in `resources/js/dashboard.js`
(`profileEditor`, `professionsManager`, `crudList`).

## Mobile API endpoints
None yet — the Sanctum token API lands in Phase 4; each endpoint will document its request/response
against the envelope above.
