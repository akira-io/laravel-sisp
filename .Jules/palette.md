## 2026-01-25 - Keyboard Accessibility for Link Buttons
**Learning:** Link buttons (anchors styled as buttons) often lack focus states because browsers don't always apply default button focus styles to anchors. This makes them invisible to keyboard users tabbing through the page.
**Action:** Always add `focus:ring` (or similar focus indicators) to anchor tags that are styled as buttons to ensure keyboard accessibility.
## 2026-01-24 - Fix Invalid Noscript Fallback
**Learning:** Found `<noscript>` block using `onclick` handler on a button. This is functionally useless as `onclick` requires JavaScript, which is exactly what `<noscript>` users don't have.
**Action:** Always use standard `<button type="submit">` or links inside `<noscript>` blocks. Ensure forms work without JS or provide a clean alternative.
## 2026-01-26 - Package View Reliability
**Learning:** Package views extending host app layouts (`@extends('layouts.app')`) are fragile and crash if the host layout is missing or incompatible.
**Action:** Use package-scoped layout components (`<x-package::layouts.app>`) for critical views (like redirects) to ensure reliable rendering and consistent styling across all host environments.
