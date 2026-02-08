---
name: Incorrect Docblocks in Transaction Model
about: The Transaction model contains incorrect property type and nullability definitions.
labels: tech-debt
---

## Description
The `Transaction` model docblocks define properties such as `transaction_id` as `int` and `status` as `TransactionStatus`, but the database schema defines them as nullable strings. `payload` is also defined as `array` but is nullable.

## Impact
Static analysis tools (e.g., PHPStan) may report false positives or miss actual type errors due to these incorrect definitions.

## Steps to Reproduce
1.  Run PHPStan on code accessing `$transaction->transaction_id` or other mismatched properties.
2.  Observe type warnings or lack thereof where appropriate.

## Suggested Fix
1.  Update `@property` tags to reflect the database schema and casts correctly.
    -   `transaction_id`: `string|null` (or check if cast to int is applied/intended).
    -   `status`: `TransactionStatus|null` (if not enforced at DB level).
    -   `payload`: `array|null`.
