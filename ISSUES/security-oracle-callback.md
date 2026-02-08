---
name: Oracle and DoS Vulnerability in PreventDuplicateCallback
about: Database queries are performed before signature validation, leading to potential oracle and DoS attacks.
labels: security, high-severity
---

## Description
The `PreventDuplicateCallback` middleware executes a database query (`Transaction::query()->where(...)`) to check for duplicate callbacks *before* the request signature is validated.

## Impact
-   **Oracle Attack:** An attacker can potentially infer valid transaction references/sessions by measuring response times or observing behavior.
-   **DoS:** An attacker can flood the callback endpoint with invalid requests, causing unnecessary database load.

## Steps to Reproduce
1.  Send a POST request to `/sisp/callback` with random `merchantRespMerchantRef` and `merchantRespMerchantSession`.
2.  Observe that a database query is executed even if the signature is invalid or missing.

## Suggested Fix
1.  Move the duplicate check to run *after* signature validation (e.g., inside the controller or a later middleware).
2.  Ensure signature validation is the first step in processing any callback.
