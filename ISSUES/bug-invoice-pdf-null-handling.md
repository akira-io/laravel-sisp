---
name: Unsafe Null Handling in Invoice PDF Generation
about: Nullable properties due_date and locale are accessed unsafely in GenerateInvoicePdfAction.
labels: bug
---

## Description
The `GenerateInvoicePdfAction` accesses `$invoice->due_date` and `$transaction->locale` without checking for null values.
The database schema defines `due_date` as nullable. `locale` is non-nullable in schema but might be null in model instances not yet persisted or incorrectly initialized.

## Impact
If `due_date` is null, the `dueAt()` method on the invoice builder might throw a TypeError or InvalidArgumentException.
If `locale` is null, `str_replace` will throw a TypeError.

## Suggested Fix
1.  Add null checks or fallback values (e.g., `?? now()->addDays(30)` or default locale).
2.  Use null-safe operators where appropriate.
