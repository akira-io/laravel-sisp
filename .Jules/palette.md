## 2026-01-25 - Keyboard Accessibility for Link Buttons
**Learning:** Link buttons (anchors styled as buttons) often lack focus states because browsers don't always apply default button focus styles to anchors. This makes them invisible to keyboard users tabbing through the page.
**Action:** Always add `focus:ring` (or similar focus indicators) to anchor tags that are styled as buttons to ensure keyboard accessibility.
## 2026-01-24 - Fix Invalid Noscript Fallback
**Learning:** Found `<noscript>` block using `onclick` handler on a button. This is functionally useless as `onclick` requires JavaScript, which is exactly what `<noscript>` users don't have.
**Action:** Always use standard `<button type="submit">` or links inside `<noscript>` blocks. Ensure forms work without JS or provide a clean alternative.
## 2026-02-12 - Focus Ring Offset on Dark Backgrounds
**Learning:** Default focus ring offsets (usually white) look jarring and broken on dark backgrounds (e.g., `bg-gray-900`), creating a "double border" effect that diminishes visual polish.
**Action:** Always match `focus:ring-offset-{color}` to the background color when using dark themes (e.g., `focus:ring-offset-gray-900`).
