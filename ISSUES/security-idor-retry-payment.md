---
name: IDOR Vulnerability in Retry Payment Endpoint
about: The retry payment endpoint allows unauthorized users to retry transactions belonging to others.
labels: security, high-severity
---

## Description
The `sisp/retry-payment` endpoint is defined as a `POST` route without route parameters or signature verification middleware. The `RetryPaymentController` retrieves the transaction using the `transaction_id` from the request body without verifying ownership or authorization.

## Impact
An attacker can enumerate transaction IDs and force a retry of any transaction, potentially causing financial loss or unauthorized state changes.

## Steps to Reproduce
1.  Identify a valid `transaction_id` (e.g., from a callback URL or enumeration).
2.  Send a POST request to `/sisp/retry-payment` with `transaction_id` in the body.
3.  Observe that the retry logic is executed regardless of the authenticated user.

## Suggested Fix
1.  Convert the route to `GET` and use Laravel's Signed Routes (`URL::signedRoute`) to generate secure URLs.
2.  Add `{transaction}` route model binding to the route definition.
3.  Apply the `signed` middleware to the route.
