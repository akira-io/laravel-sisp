---
name: Information Disclosure/DoS Oracle in Callback Endpoint
about: The `PreventDuplicateCallback` middleware performs database lookups before signature verification.
labels: security, bug
---

**Describe the bug**
The `PreventDuplicateCallback` middleware attempts to retrieve a `Transaction` from the database using `merchant_ref` and `merchant_session` provided in the request body, *before* the request signature is validated (which happens later in the chain or controller). This creates an oracle where an attacker can determine if a transaction exists by observing the response time or status (redirect vs processing).

```php
// Akira/Sisp/Http/Middleware/PreventDuplicateCallback.php

public function handle(Request $request, Closure $next): Response
{
    // ...
    if ($this->isAlreadyProcessed($request)) { // This calls DB
        // ...
    }
}
```

**Impact**
1. **Information Disclosure (Oracle)**: Attackers can enumerate existing `merchant_ref` and `merchant_session` combinations.
2. **Denial of Service (DoS)**: Attackers can force the application to perform expensive database lookups without providing a valid signature, potentially overloading the database.

**To Reproduce**
1. Send requests to `/sisp/callback` with random `merchantRespMerchantRef` and `merchantRespMerchantSession` values.
2. Observe the different response behavior/timing when a valid combination is guessed (redirect) versus invalid (processed).

**Suggested Fix**
1. Move the database lookup *after* `Sisp::validateCallback($payload)` (signature check).
2. Ensure the signature check is the very first step in processing any callback.
