---
name: Incorrect Base64 Decoding in EncryptsAttributes Trait
about: Using strict mode in base64_decode can reject valid encrypted strings.
labels: tech-debt
---

## Description
The `EncryptsAttributes::isEncrypted` method uses `base64_decode($value, true)`. In strict mode, `base64_decode` rejects strings containing characters outside the base64 alphabet. It has been observed that strict validation can falsely reject valid Laravel encrypted strings.

## Impact
Potential for valid encrypted values to be treated as unencrypted, causing decryption failures or data corruption.

## Suggested Fix
1.  Remove the `true` (strict) parameter from `base64_decode`.
2.  Remove the `if ($decoded === false)` check as loose decoding always returns a string.
3.  Rely on `json_decode` and structure validation (checking for `iv`, `value`, `mac`) to confirm if it's a valid encrypted payload.
