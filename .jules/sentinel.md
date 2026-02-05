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

## 2025-02-17 - [IDOR in Retry Payment]
**Vulnerability:** The `RetryPaymentController` used `Transaction::query()->findOrFail($request->integer('transaction_id'))` to retrieve the transaction, allowing any user to retry any payment by guessing the ID.
**Learning:** Publicly accessible "stateless" actions (where the user might not be logged in) must use Signed Routes to verify the request's authenticity and parameters.
**Prevention:** Use `URL::signedRoute` for actions like retries, cancellations, or email verifications, and ensure the controller accepts the model via Route Model Binding (which implicitly uses the signed parameter) or validate the signature middleware explicitly.
