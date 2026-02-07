---
name: Unused Blade Views
about: The codebase contains Blade views that are not referenced anywhere.
labels: tech-debt
---

**Describe the tech debt**
The `resources/views` directory contains two unused views:
1. `purchase-cancelled.blade.php`
2. `purchase-success.blade.php`

These views are not referenced by any controller or action (`view(...)`, `view::make(...)`, or blade `@extends` within the `src/` directory). The `payment-response.blade.php` seems to be the preferred template.

**Impact**
This is unnecessary technical debt and code bloat. It can confuse future maintainers who might modify these files expecting changes to reflect in the application, or waste time figuring out their purpose.

**To Reproduce**
1. Search the codebase for usages of `purchase-cancelled` and `purchase-success`.
2. Confirm no matches are found in source files or config.

**Suggested Fix**
1. Delete the unused views.
2. If they are intended for future use or backward compatibility, mark them as deprecated or document their usage in `README.md`.
