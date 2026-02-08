---
name: Double Encoding in EncryptsAttributes Trait
about: Encrypted attributes are double-encoded when combined with Eloquent array/json casts.
labels: bug
---

## Description
When `EncryptsAttributes` is used on a model attribute that is also cast to `array` or `json`, Eloquent JSON-encodes the encrypted string (which is already a string) before storing it. This results in a quoted JSON string in the database (e.g., `"eyJ..."`).
The `getAttribute` method retrieves this raw quoted string but passes it directly to `Crypt::decryptString`, which expects the unquoted payload.

## Impact
Decryption fails for attributes that are both encrypted and cast to array/json, as `Crypt::decryptString` receives an invalid payload (JSON string containing the encrypted string).

## Suggested Fix
1.  In `getAttribute`, check if the raw attribute is a JSON-encoded string wrapping the encrypted payload.
2.  If so, `json_decode` it once to get the actual encrypted string before decrypting.
