# Introduction

The Fama mobile API (v1) — token-authenticated endpoints for talents, brands and admins, sharing the same services and JSON envelope as the web app.

<aside>
    <strong>Base URL</strong>: <code>https://fama.test</code>
</aside>

    All endpoints live under `/api/v1` and return Fama's single JSON envelope:

    ```json
    { "success": true, "data": {}, "message": null, "errors": null, "meta": null }
    ```

    Paginated lists put page info at `meta.pagination`. Send `Accept-Language: ar` (or `en`)
    to receive translatable fields in that locale. Authenticate by sending the bearer token
    returned from a login/register endpoint as `Authorization: Bearer {token}`.

