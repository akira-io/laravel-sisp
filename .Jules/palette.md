## 2026-01-25 - Keyboard Accessibility for Link Buttons
**Learning:** Link buttons (anchors styled as buttons) often lack focus states because browsers don't always apply default button focus styles to anchors. This makes them invisible to keyboard users tabbing through the page.
**Action:** Always add `focus:ring` (or similar focus indicators) to anchor tags that are styled as buttons to ensure keyboard accessibility.
## 2026-01-24 - Fix Invalid Noscript Fallback
**Learning:** Found `<noscript>` block using `onclick` handler on a button. This is functionally useless as `onclick` requires JavaScript, which is exactly what `<noscript>` users don't have.
**Action:** Always use standard `<button type="submit">` or links inside `<noscript>` blocks. Ensure forms work without JS or provide a clean alternative.
## 2026-01-26 - Semantic Feedback and Reduced Motion
**Learning:** Hardcoded "Success" colors (Green) on "Cancelled" pages confuse users about the transaction outcome. Also, infinite animations (pulse/spin) on status pages can trigger vestibular disorders if not wrapped in `prefers-reduced-motion` queries.
**Action:** Use neutral or primary variants for non-success actions, and always wrap continuous animations in `@media (prefers-reduced-motion: no-preference)`. Ensure loaders have `role="status"`.
