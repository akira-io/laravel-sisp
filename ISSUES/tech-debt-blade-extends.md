---
name: Blade Views Should Not Extend Host Layout Directly
about: Package views are extending layouts.app instead of a package-specific layout.
labels: tech-debt
---

## Description
Package views (e.g., `payment-form.blade.php`) are using `@extends('layouts.app')`. This assumes the host application has a `layouts.app` file and may cause styling conflicts or missing assets.
Additionally, extending the host layout often results in duplicate `<body>` tags if not handled correctly.

## Impact
-   **Rendering Errors:** If the host application uses a different layout name or structure, the package views will fail to render.
-   **Styling Inconsistencies:** The package cannot guarantee the presence of Tailwind or other dependencies in the host layout.

## Suggested Fix
1.  Create a package-specific layout component (e.g., `x-sisp::layouts.app`).
2.  Update all package views to use `<x-sisp::layouts.app>` instead of `@extends('layouts.app')`.
