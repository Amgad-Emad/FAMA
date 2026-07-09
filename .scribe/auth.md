# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Obtain a token from one of the <b>login</b> or <b>register</b> endpoints, then send it as <code>Authorization: Bearer {token}</code>. Tokens are ability-scoped to the entity (talent / brand / admin) they belong to.
