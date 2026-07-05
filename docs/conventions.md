# Conventions

> How we write Fama code. Kept in sync with the code; update in place.

## Controllers & services
- Controllers are **thin**: validate via a Form Request, build a DTO, call a service, return a
  `Response`. No queries or business logic in controllers.
- Business logic lives in `App\Services\*` (extend `App\Services\Service`). Multi-write operations run
  through `runInTransaction()` so they're atomic and get failure logging for free.
- Discrete operations are `App\Actions\*` invokable classes implementing
  `App\Actions\Contracts\Action`; services orchestrate them.

## The JSON envelope (web Ajax + API)
Every controller action returns the same shape:

```json
{ "success": true, "data": {}, "message": null, "errors": null, "meta": null }
```

Use the macros (registered in `AppServiceProvider`):
- `response()->success($data, $message, $meta, $status)`
- `response()->error($message, $errors, $status, $data, $meta)`
- `response()->paginated($paginator, ...)` — items become `data`, pagination goes to `meta.pagination`.

Validation/authentication exceptions are rendered into this envelope automatically for JSON/Ajax
requests (`bootstrap/app.php`). `resources/js/http.js` parses it and throws `ApiError` with the field
error bag.

## Failure-logging convention
Catch → log to the **dedicated channel** with context → rethrow (or return an error envelope):

```php
try {
    return DB::transaction(fn () => /* ... */);
} catch (\Throwable $e) {
    Log::channel('deals')->error($e->getMessage(), ['deal_id' => $id, 'exception' => $e]);
    throw $e;
}
```

`Service::runInTransaction($cb, $context, $channel)` encapsulates exactly this. Channels: `app`
(default), `auth`, `deals`, `media`.

## Transactions
Any operation that writes to more than one table/row is wrapped in a transaction (via
`Service::runInTransaction()` or `DB::transaction()`).

## Models
- `preventLazyLoading` and `preventSilentlyDiscardingAttributes` are on in non-production — **eager
  load** relations (`with(...)`) and only mass-assign declared attributes.
- Lists are **always eager-loaded and always paginated**.

## Media (spatie/laravel-medialibrary)
- All **uploaded** files go through media collections + conversions. Thumbnails are **conversions**,
  never manual `*_url`/`thumbnail_url` columns; those columns (for uploads) become accessors that
  resolve from the media library.
- **External** links/embeds (YouTube/Vimeo, social, brand-collab, press) stay as plain URL columns.
- See `docs/decisions.md` and `docs/specs/schema-master.md` (implementation note).

## Translatable attributes (spatie/laravel-translatable)
Fama is bilingual (en/ar). Policy:
- **Translate** free-text, human-facing copy that a user would reasonably localise: e.g. `headline`,
  `bio`, block `title`, service `name`/`description`, case-study `title`/`summary`/`body`, campaign
  `title`/`description`, deal-flow step `name`/`instructions`.
- **Do NOT translate:** identifiers, slugs, enums, emails, numbers, dates, URLs, foreign keys, or
  machine keys (`block_types.key`, `deal_flow_steps.key`).
- Storage: translatable columns are `JSON`, declared via `use HasTranslations;` +
  `public array $translatable = ['headline', 'bio', ...];`. Read/write resolve to the active locale;
  use `->getTranslation($attr, $locale)` / `->setTranslation(...)` for explicit locales.
- Validation: validate the submitted locale's value; keep the other locale untouched on partial edits.
- Fallback: missing translations fall back to `APP_FALLBACK_LOCALE` (en).

## RTL / i18n in views
- Set direction from the locale (`<html dir>` in the layouts). Prefer **logical** Tailwind utilities
  (`ms-*`, `me-*`, `ps-*`, `pe-*`, `text-start`, `text-end`) over `left/right` so Arabic mirrors
  automatically. Use `rtl:` / `ltr:` variants only for genuinely direction-specific tweaks.
- Wrap user-facing strings in `__()` / translation files; never hardcode copy in Blade.
- Link to other locales with the `x-language-switcher` component.

## Naming
- Tables/columns: snake_case, plural tables (per `docs/specs/schema-master.md`).
- Classes: `SomethingService`, `SomethingAction`, `SomethingData`, `SomethingPolicy`,
  `SomethingResource`.
- Routes: dashed URLs, dotted names, guard-prefixed (`admin.`, `brand.`, `talent.`).

## Testing (Pest)
- Feature tests for every route/flow; unit tests for services/actions/support.
- Assert the envelope shape for JSON endpoints. Tests run on in-memory SQLite (`RefreshDatabase`).
- Definition of Done: tests green, fail-logs + transactions verified, docs updated, no git.
