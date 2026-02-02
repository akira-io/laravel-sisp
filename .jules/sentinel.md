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
