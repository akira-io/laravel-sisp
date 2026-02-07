---
name: IDOR Vulnerability in Retry Payment Endpoint
about: The `RetryPaymentController` allows unauthorized users to retry any transaction.
labels: security, bug
---

**Describe the bug**
The `RetryPaymentController` retrieves a `Transaction` using `findOrFail` with the `transaction_id` provided in the request body, without verifying if the authenticated user (or session context) owns that transaction.

```php
// Akira/Sisp/Http/Controllers/RetryPaymentController.php

public function __invoke(RetryPaymentRequest $request): mixed
{
    $transaction = Transaction::query()->findOrFail($request->integer('transaction_id'));
    // ...
}
```

The `RetryPaymentRequest` also authorizes all requests (`return true;`).

**Impact**
This is an Insecure Direct Object Reference (IDOR) vulnerability. An attacker can iterate through transaction IDs and retry payments for transactions belonging to other users. This could lead to unauthorized charges, confusion, or disruption of service.

**To Reproduce**
1. Send a POST request to `/sisp/retry-payment` with a `transaction_id` belonging to another user.
2. Observe that the system processes the retry request without error.

**Suggested Fix**
1. Enforce ownership checks. Use `Auth::user()->transactions()->findOrFail(...)` or a Policy/Gate check.
2. If this endpoint is intended to be stateless/public, implement Signed Routes (`URL::signedRoute`) and include the `transaction_id` in the signed parameters, or use a secure token.
