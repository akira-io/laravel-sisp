## 2024-05-23 - Dependency Injection Optimization & Environment Mismatch
**Learning:** `LoadConfig` class was resolving `Config\Repository` via `$app->make()` in every getter method, causing unnecessary container overhead. Refactoring to constructor injection improves performance.
**Action:** Prefer constructor injection for singletons or long-lived objects to avoid repeated container resolution.

**Learning:** The development environment runs PHP 8.3, but the project dependencies (specifically `akira/laravel-debugger`) use PHP 8.4 syntax (`new Class()->method()`). This prevents running tests locally without upgrading PHP.
**Action:** Be aware of environment limitations and rely on static analysis/linting when running tests is not possible due to platform constraints.
