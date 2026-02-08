---
name: IDOR Vulnerability in Refund Transaction Endpoint
about: The refund endpoint lacks specific authorization checks for transaction ownership.
labels: security, high-severity
---

## Description
The `RefundTransactionController` uses middleware (e.g., `web`, `auth`) but does not explicitly verify if the authenticated user has permission to refund the specific transaction (IDOR).

## Impact
An authenticated user (e.g., in a multi-tenant system) can potentially refund transactions belonging to other users or merchants by guessing the `transaction_id`.

## Steps to Reproduce
1.  Authenticate as User A.
2.  Send a request to `/sisp/refund/{transaction_id_of_user_B}`.
3.  Observe that the refund action is initiated without permission checks.

## Suggested Fix
1.  Implement an authorization policy or gate for refund actions.
2.  Ensure `RefundTransactionController` checks `Gate::authorize('refund', $transaction)` or similar before proceeding.
