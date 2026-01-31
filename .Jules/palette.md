## 2026-01-25 - Keyboard Accessibility for Link Buttons
**Learning:** Link buttons (anchors styled as buttons) often lack focus states because browsers don't always apply default button focus styles to anchors. This makes them invisible to keyboard users tabbing through the page.
**Action:** Always add `focus:ring` (or similar focus indicators) to anchor tags that are styled as buttons to ensure keyboard accessibility.
## 2026-01-24 - Fix Invalid Noscript Fallback
**Learning:** Found `<noscript>` block using `onclick` handler on a button. This is functionally useless as `onclick` requires JavaScript, which is exactly what `<noscript>` users don't have.
**Action:** Always use standard `<button type="submit">` or links inside `<noscript>` blocks. Ensure forms work without JS or provide a clean alternative.

## 2026-02-14 - Infinite Animation Accessibility
**Learning:** The application extensively uses infinite animations (pulse, spin) for loading states and attention grabbers. These can trigger vestibular disorders and are not guarded by `prefers-reduced-motion`.
**Action:** Always wrap infinite animations in `motion-reduce:animate-none` (Tailwind) or `@media (prefers-reduced-motion: reduce) { animation: none }` to respect user settings.
