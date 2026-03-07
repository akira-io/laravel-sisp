## 2026-02-07 - Memoization in Readonly Classes
**Learning:** In `final readonly` classes, lazy memoization (e.g., in `handle()` method) is impossible because properties cannot be mutated after construction.
**Action:** Move expensive calculations that depend on immutable state (like configuration) to the `__construct` method. This effectively memoizes the result for the instance's lifetime.
