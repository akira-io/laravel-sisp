## 2024-05-22 - [Callback Validation Race Condition]
**Vulnerability:** The application was updating the transaction status based on the callback payload *before* validating the request signature (fingerprint). This allowed an attacker to forge a successful payment callback without knowing the secret key, as the system would mark the transaction as paid before rejecting the request.
**Learning:** Logic order in callback handlers is critical. Always validate the authenticity of the request (signature, token, IP allowlist) before processing any data or updating state. It is a common mistake to "parse first, validate later" which can lead to state corruption.
**Prevention:** Ensure that verification steps (signature checks, authentication) are the very first operations in any webhook or callback handler. Use "Guard Clauses" to return early if validation fails.

## 2025-05-22 - [Pre-Validation Database Lookup & Event Trigger]
**Vulnerability:** The callback handler performed a database lookup (`findOrCreateTransaction`) and dispatched an event (`PaymentFailed`) *before* validating the signature. This allowed unauthenticated payloads to trigger database queries (potential enumeration/DoS) and application events.
**Learning:** Even if state updates are guarded, performing *any* expensive operation or side-effect (like DB lookups or events) before validation exposes the application to DoS and information leakage.
**Prevention:** Validation must be strict and immediate. Do not resolve models or dispatch events until the payload is verified authentic.
