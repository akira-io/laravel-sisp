---
name: Bug/Type Safety Issue in Generate Invoice PDF Action
about: The `GenerateInvoicePdfAction` action assumes non-null values for invoice and transaction properties.
labels: bug
---

**Describe the bug**
The `GenerateInvoicePdfAction` accesses `$invoice->due_date` and `$transaction->locale` without null checks.

```php
// Akira/Sisp/Actions/GenerateInvoicePdfAction.php

public function handle(Invoice $invoice): string
{
    // ...
    ->dueAt($invoice->due_date) // If due_date is null, this might fail (depending on strict types or library)
    ->locale(str_replace('-', '_', $transaction->locale)) // If locale is null, str_replace throws TypeError
    // ...
}
```

Since the `Transaction` model and `Invoice` model likely allow `NULL` for these fields (as is common for optional data), strict type checking will cause crashes.

**Impact**
This leads to runtime errors (`TypeError`) when generating PDFs for invoices/transactions with missing data, potentially breaking the payment flow or admin features.

**To Reproduce**
1. Create a `Transaction` with `locale = null` or an `Invoice` with `due_date = null`.
2. Call `GenerateInvoicePdfAction::handle($invoice)`.
3. Observe the `TypeError: str_replace(): Argument #3 ($subject) must be of type array|string, null given`.

**Suggested Fix**
1. Add fallback values or null checks: `str_replace('-', '_', $transaction->locale ?? 'pt-CV')`.
2. Ensure `Invoice` creation validates `due_date` or handle it gracefully.
