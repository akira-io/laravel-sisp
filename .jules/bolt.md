## 2024-05-23 - Premature Array Memoization
**Learning:** PHP's internal handling of constant arrays (returning `[...]`) is extremely efficient (likely interned/COW). Manually memoizing a static array with a property check (`if (self::$cache)`) was actually slower and introduced shared mutable state risks.
**Action:** Do not memoize static constant data unless it involves expensive computation. Trust PHP's OPcache for static arrays.

## 2024-05-23 - Exception Overhead in Encryption Check
**Learning:** `EncryptsAttributes` trait was using `try-catch` around `Crypt::decryptString` to determine if a value was encrypted. This caused significant overhead (~10x slower check) when saving raw JSON data, as it threw exceptions for every transaction creation.
**Action:** Use cheap string inspection (e.g. `str_starts_with`) to filter out obvious non-encrypted values before attempting expensive operations that might throw.
## 2024-05-23 - Dependency Injection Optimization & Environment Mismatch
**Learning:** `LoadConfig` class was resolving `Config\Repository` via `$app->make()` in every getter method, causing unnecessary container overhead. Refactoring to constructor injection improves performance.
**Action:** Prefer constructor injection for singletons or long-lived objects to avoid repeated container resolution.

**Learning:** The development environment runs PHP 8.3, but the project dependencies (specifically `akira/laravel-debugger`) use PHP 8.4 syntax (`new Class()->method()`). This prevents running tests locally without upgrading PHP.
**Action:** Be aware of environment limitations and rely on static analysis/linting when running tests is not possible due to platform constraints.

## 2026-01-29 - Expensive Decryption Check
**Learning:** Checking if a string is encrypted by attempting to decrypt it (`Crypt::decryptString`) is expensive even when successful (~12us). A structural check (Base64 + JSON + keys) is significantly faster (~4us) and avoids crypto overhead.
**Action:** Validate encryption status using lightweight structural checks (Base64 + JSON keys) instead of full decryption when security verification is not immediately required (e.g. state checks).

## 2026-05-25 - Optimizing Attribute Retrieval
**Learning:** `EncryptsAttributes::getAttribute` was incurring exception overhead for unencrypted values by blindly attempting decryption. Implementing the lightweight `mightBeEncrypted` check (from previous learnings) in the read path eliminates this overhead and improves performance for mixed data models.
**Action:** Ensure expensive operations in hot paths (like attribute accessors) are guarded by cheap pre-checks, especially when they involve exception handling.
