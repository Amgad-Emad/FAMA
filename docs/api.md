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

## Endpoints
None yet (Phase 0). API routes land with their features; each documents its request/response against the
envelope above.
