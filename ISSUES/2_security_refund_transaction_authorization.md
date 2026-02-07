---
name: Missing Authorization Check in Refund Transaction Endpoint
about: Any authenticated user can refund any transaction.
labels: security, bug
---

**Describe the bug**
The `RefundTransactionController` uses Laravel's Route Model Binding but lacks an explicit authorization check (`Gate::authorize`, `authorize`, etc.). Although the route might be protected by `auth` middleware, this only ensures the user is logged in, not that they are authorized to refund *that specific* transaction.

```php
// Akira/Sisp/Http/Controllers/RefundTransactionController.php

public function __invoke(Transaction $transaction, Request $request): JsonResponse
{
    // No authorization check here!
    $refundAmount = (float) $request->input('amount');
    // ...
}
```

**Impact**
This is an IDOR vulnerability. Any user with access to the endpoint (e.g., any logged-in user if the middleware is just `auth`) can refund any transaction by guessing the transaction ID. This leads to direct financial loss and unauthorized refunds.

**To Reproduce**
1. As user A, initiate a refund request for a transaction belonging to user B (by changing the ID in the URL `/sisp/refund/{transaction_id}`).
2. Observe that the refund is processed successfully.

**Suggested Fix**
1. Implement a Policy for `Transaction` model (e.g., `refund` ability).
2. Call `$this->authorize('refund', $transaction)` inside the controller or use middleware `can:refund,transaction`.
3. Provide a way to customize the authorization logic (e.g., `Sisp::auth(...)` callback) for package consumers.
