## 2024-05-22 - [Callback Validation Race Condition]
**Vulnerability:** The application was updating the transaction status based on the callback payload *before* validating the request signature (fingerprint). This allowed an attacker to forge a successful payment callback without knowing the secret key, as the system would mark the transaction as paid before rejecting the request.
**Learning:** Logic order in callback handlers is critical. Always validate the authenticity of the request (signature, token, IP allowlist) before processing any data or updating state. It is a common mistake to "parse first, validate later" which can lead to state corruption.
**Prevention:** Ensure that verification steps (signature checks, authentication) are the very first operations in any webhook or callback handler. Use "Guard Clauses" to return early if validation fails.

## 2024-05-24 - [Database Access before Signature Validation]
**Vulnerability:** `HandleCallbackAction` queried the database for a transaction using parameters from an unverified payload. This exposed the application to Denial of Service (DoS) via database exhaustion and potential timing attacks/oracles on transaction existence.
**Learning:** Even read-only operations (like `findOrFail`) shouldn't happen before input validation if they rely on external input. Security checks must be the absolute first step.
**Prevention:** Strictly enforce "Validate First, Ask Questions Later". Do not touch the database, file system, or other services until the request signature/authenticity is verified.

## 2024-05-23 - [Unvalidated Database Access in Callback]
**Vulnerability:** The `HandleCallbackAction` performed a database lookup/creation (`findOrCreateTransaction`) using the payload data *before* validating the callback signature. This exposed the database to potential DoS attacks and timing attacks from unauthenticated sources.
**Learning:** Even read-only database operations (or find-or-create) should be protected by signature validation when handling external callbacks. Processing untrusted input against the database is a security risk.
**Prevention:** Strictly enforce "Validate First" policy. The very first line of code in a callback handler should be the signature validation. Do not touch the database or filesystem until the request is proven authentic.

## 2025-01-30 - [Unprotected Package Routes]
**Vulnerability:** The package exposed sensitive administrative routes (e.g., `sisp/refund`) via `routes/web.php` without any authentication or authorization middleware configured by default. This allowed any user to trigger refunds if they could guess the transaction ID, bypassing application-level security controls.
**Learning:** Package routes are automatically registered in the host application. If they perform sensitive actions, they MUST use configurable middleware (defaulting to secure options like `web` and `auth`) to ensure they are protected according to the host application's security context. Hardcoding unprotected routes in a package assumes a level of trust that does not exist for public endpoints.
**Prevention:** Always expose middleware configuration for package routes in the config file. Set secure defaults (e.g., `['web', 'auth']`) for any route that modifies state or exposes sensitive data.

## 2025-01-31 - [Unsecured Retry Payment IDOR]
**Vulnerability:** The `sisp/retry-payment` endpoint was vulnerable to Insecure Direct Object Reference (IDOR). It accepted a `transaction_id` via POST body without any authentication or signature verification, allowing anyone to retry (and potentially view details of) any transaction by guessing its ID.
**Learning:** "Stateless" actions that need to be accessible to guest users (like retrying a payment after a callback) cannot rely on `auth` middleware but must be secured against manipulation.
**Prevention:** Use Laravel's Signed Routes (`URL::signedRoute` and `middleware('signed')`) for any action link that grants capabilities to a guest user. This ensures the request parameters (like transaction ID) have not been tampered with since the link was generated.
