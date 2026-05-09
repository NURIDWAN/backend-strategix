# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_SANCTUM_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Login via <code>POST /api/auth/login</code> untuk mendapatkan Sanctum token, kemudian kirim sebagai header <code>Authorization: Bearer {token}</code>.
