---
name: Optimize PostAutCode Hash Calculation
about: Hash calculation in PostAutCode should be moved to the constructor for performance.
labels: performance
---

## Description
The `PostAutCode` class calculates the hash of the `posAutCode` in the `handle` method. Since the class is `final readonly` and the input is configuration-based (and thus static per request), this calculation should be memoized or computed once in the constructor.

## Impact
Unnecessary recalculation of SHA-512 hash on every call, leading to performance overhead. Benchmarks indicate ~80x potential improvement.

## Suggested Fix
1.  Calculate the hash in the `__construct` method and store it in a property.
2.  Return the stored hash in `handle`.
