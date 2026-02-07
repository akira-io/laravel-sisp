---
name: Incorrect Docblocks for Transaction Model
about: The `Transaction` model defines properties as non-nullable, conflicting with actual database schema/usage.
labels: tech-debt
---

**Describe the tech debt**
The `Transaction` model's docblocks define several properties as non-nullable, but their database columns or initial states likely allow `NULL`.

Example:
```php
// Akira/Sisp/Models/Transaction.php

/**
 * @property-read TransactionStatus $status
 * @property-read int $transaction_id
 */
```
The `status` property may be `NULL` (e.g., before initialization) or default to `PENDING` (if not defined). The `transaction_id` might be `NULL` before a successful response from the payment gateway (since it's typically assigned *after* the request).

**Impact**
This leads to incorrect static analysis results (PHPStan/Psalm/IDE) and potential runtime `TypeError`s if code relies on these properties being non-null (e.g., passing them to functions requiring non-null types).

**To Reproduce**
1. Create a `Transaction` instance without setting `status` or `transaction_id`.
2. Inspect the property values (they are `null`).
3. Pass `$transaction->status` to a function expecting `TransactionStatus` (not nullable). It will crash or throw a `TypeError`.

**Suggested Fix**
1. Update docblocks to reflect reality: `@property-read TransactionStatus|null $status`, `@property-read int|null $transaction_id`.
2. Ensure database schema matches these expectations.
