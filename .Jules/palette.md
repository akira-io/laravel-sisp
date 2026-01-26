## 2026-01-24 - Fix Invalid Noscript Fallback
**Learning:** Found `<noscript>` block using `onclick` handler on a button. This is functionally useless as `onclick` requires JavaScript, which is exactly what `<noscript>` users don't have.
**Action:** Always use standard `<button type="submit">` or links inside `<noscript>` blocks. Ensure forms work without JS or provide a clean alternative.
