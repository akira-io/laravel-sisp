---
name: Performance Optimization in PostAutCode Action
about: The `PostAutCode` action recalculates a static hash on every invocation.
labels: performance
---

**Describe the bug**
The `PostAutCode` action is a `final readonly` class that calculates a SHA-512 hash (`base64_encode(hash('sha512', $posAutCode, true))`) in its `handle` method. The input `$posAutCode` typically comes from configuration/credentials and is static per request (or per scope).

```php
// Akira/Sisp/Actions/PostAutCode.php

final readonly class PostAutCode
{
    // ...
    public function handle(): string
    {
        $posAutCode = $this->resolver->resolve()->posAutCode; // Often static
        return base64_encode(hash('sha512', $posAutCode, true));
    }
}
```

Since this class is `readonly`, memoization via lazy property initialization is not possible. However, the calculation could be moved to the `__construct` method to compute it once upon instantiation (assuming the `PostAutCode` instance lifecycle aligns with the credential scope).

**Impact**
This is a micro-optimization issue. While SHA-512 is fast, recalculating it repeatedly is wasteful if the input is static.

**To Reproduce**
1. Call `PostAutCode::handle()` multiple times in a loop.
2. Observe the repeated execution of `hash` function.

**Suggested Fix**
1. Move the calculation to the constructor:
   ```php
   public function __construct(SispCredentialsResolver $resolver) {
       $posAutCode = $resolver->resolve()->posAutCode;
       $this->hash = base64_encode(hash('sha512', $posAutCode, true));
   }
   public function handle(): string { return $this->hash; }
   ```
2. Ensure the `PostAutCode` instance is appropriately scoped (e.g., singleton per request or transient per scoped Sisp instance).
