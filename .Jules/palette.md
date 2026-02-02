## 2026-01-25 - Keyboard Accessibility for Link Buttons
**Learning:** Link buttons (anchors styled as buttons) often lack focus states because browsers don't always apply default button focus styles to anchors. This makes them invisible to keyboard users tabbing through the page.
**Action:** Always add `focus:ring` (or similar focus indicators) to anchor tags that are styled as buttons to ensure keyboard accessibility.
## 2026-01-24 - Fix Invalid Noscript Fallback
**Learning:** Found `<noscript>` block using `onclick` handler on a button. This is functionally useless as `onclick` requires JavaScript, which is exactly what `<noscript>` users don't have.
**Action:** Always use standard `<button type="submit">` or links inside `<noscript>` blocks. Ensure forms work without JS or provide a clean alternative.

## 2026-01-26 - Valid HTML Structure and Auto-Redirect Fallbacks
**Learning:** Blade templates extending layouts that already contain a `<body>` tag should NOT include their own `<body>` tag. Nested `<body>` tags are invalid HTML and can cause rendering issues or prevent events (like `onload`) from firing reliably. Also, relying solely on JS for auto-redirection leaves users stranded if JS fails.
**Action:** Use a semantic wrapper (e.g., `<div>`) instead of `<body>` in child templates. Always provide a visible "Click here if not redirected" button and a `<noscript>` block with a submit button for auto-submitting forms.
