# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer Bearer {YOUR_ACCESS_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Obtain your access token by calling the <b>POST /api/auth/login</b> endpoint with your credentials. Include the token in the Authorization header as: <code>Bearer {token}</code>
