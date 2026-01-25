## 2026-01-25 - Keyboard Accessibility for Link Buttons
**Learning:** Link buttons (anchors styled as buttons) often lack focus states because browsers don't always apply default button focus styles to anchors. This makes them invisible to keyboard users tabbing through the page.
**Action:** Always add `focus:ring` (or similar focus indicators) to anchor tags that are styled as buttons to ensure keyboard accessibility.
