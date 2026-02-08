---
name: High Memory Usage in Sisp::getTransactions
about: Sisp::getTransactions returns a Collection, loading all records into memory.
labels: performance
---

## Description
The `Sisp::getTransactions()` method executes `Transaction::query()->get()`, which retrieves all transaction records from the database and instantiates Eloquent models for them.

## Impact
As the number of transactions grows, this will consume increasing amounts of memory, eventually leading to Out of Memory (OOM) errors.

## Suggested Fix
1.  Refactor `Sisp::getTransactions` to return an `Illuminate\Database\Eloquent\Builder` instance instead of a `Collection`.
2.  Allow the caller to paginate or filter the query as needed.
