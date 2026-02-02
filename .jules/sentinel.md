## 2024-05-22 - [Callback Validation Race Condition]
**Vulnerability:** The application was updating the transaction status based on the callback payload *before* validating the request signature (fingerprint). This allowed an attacker to forge a successful payment callback without knowing the secret key, as the system would mark the transaction as paid before rejecting the request.
**Learning:** Logic order in callback handlers is critical. Always validate the authenticity of the request (signature, token, IP allowlist) before processing any data or updating state. It is a common mistake to "parse first, validate later" which can lead to state corruption.
**Prevention:** Ensure that verification steps (signature checks, authentication) are the very first operations in any webhook or callback handler. Use "Guard Clauses" to return early if validation fails.

## 2026-02-02 - [Unverified Database Writes]
**Vulnerability:** Although status updates were protected, the application still performed database lookups and potential record creation (`FindOrCreateTransaction`) before validating the callback signature. This exposed the database to pollution and potential DoS via spoofed requests.
**Learning:** Even read/create operations (like finding a user or transaction) should be protected by signature validation in webhooks. Any interaction with the persistence layer is a potential attack vector if the request is unverified.
**Prevention:** Strictly enforce "Validation First" policy. No `Model::find`, `Repo::get`, or similar calls should occur before `validateSignature()`.
