---
name: Performance Issue in `Sisp::getTransactions()`
about: The `getTransactions()` method loads all transactions into memory.
labels: performance
---

**Describe the bug**
The `Akira\Sisp\Sisp::getTransactions()` method returns a full `Collection` of all transaction records, using `get()`.

```php
// Akira/Sisp/Sisp.php

public function getTransactions(): Collection
{
    return Transaction::query()->get();
}
```

**Impact**
This is a critical performance issue. If the transactions table grows (e.g., thousands or millions of records), loading all of them into memory will exhaust PHP's memory limit (OOM error) and crash the application.

**To Reproduce**
1. Seed the transactions table with 100,000+ records.
2. Call `Sisp::getTransactions()` in a loop or memory profiler.
3. Observe the memory usage spike or `Allowed memory size exhausted` fatal error.

**Suggested Fix**
1. Return `Illuminate\Database\Eloquent\Builder` instead of `Collection` to allow consumers to add filters, pagination, or query modifications.
2. Change the return type hint to `Builder`.
3. Alternatively, accept pagination parameters and return a `LengthAwarePaginator`.
