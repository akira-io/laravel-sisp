---
name: Flaky Test in CheckRateLimitActionTest
about: The test "resets when window elapsed" uses a 1-second window, causing failures on slow CI runners.
labels: bug
---

## Description
The test case `resets when window elapsed then counts again` in `CheckRateLimitActionTest.php` sets a window of 1 second.
It then asserts that `$fresh->reset_at->isFuture()`.
If the test runner takes more than 1 second between the action execution and the assertion, the test fails.

## Impact
CI builds fail intermittently (flaky tests).

## Suggested Fix
Increase the window duration in the test to a safer value (e.g., 60 seconds) to tolerate execution delays.
