---
name: Missing Route Parameter for CancelTransactionController
about: The route definition for cancel transaction does not match the controller signature.
labels: bug
---

## Description
The `CancelTransactionController` expects a `Transaction` model instance in its `__invoke` method. However, the route definition `sisp/cancel` lacks a `{transaction}` parameter, causing route model binding to fail.

## Impact
The `/sisp/cancel` endpoint will throw an error when accessed, as Laravel cannot resolve the `Transaction` dependency.

## Steps to Reproduce
1.  Access `/sisp/cancel`.
2.  Observe a 404 or 500 error due to missing dependency resolution.

## Suggested Fix
1.  Update the route definition to include `{transaction}`.
2.  Or update the controller to retrieve the transaction from query parameters (e.g., `ref`) if that is the intended design (and secure it properly).
