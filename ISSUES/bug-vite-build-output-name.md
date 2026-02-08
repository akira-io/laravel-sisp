---
name: Mismatch in Vite Build Output Name
about: The Vite build process outputs CSS with an incorrect filename.
labels: bug
---

## Description
The `pnpm build` (or `vite build`) command is configured in `vite.config.js` to output `sisp.css`. However, the actual output file observed is `sisp2.css`, which causes issues when referencing the asset.

## Impact
The stylesheet `sisp.css` may not be found if the build system outputs `sisp2.css` (or vice-versa), leading to missing styles in the package views.

## Suggested Fix
1.  Verify the `vite.config.js` configuration.
2.  Check for any conflicting plugins or configuration overrides.
3.  Ensure the output name matches the expected name in the package.
