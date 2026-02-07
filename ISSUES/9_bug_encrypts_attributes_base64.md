---
name: Bug/Flaky Encryption Check in EncryptsAttributes Trait
about: The `EncryptsAttributes` trait uses strict Base64 decoding for validation, potentially rejecting valid values.
labels: bug
---

**Describe the bug**
The `EncryptsAttributes` trait attempts to validate if a value is encrypted by checking if it's a base64-encoded string using strict mode.

```php
// Akira/Sisp/Traits/EncryptsAttributes.php

private function isEncrypted(mixed $value): bool
{
    // ...
    $decoded = base64_decode($value, true); // Strict mode
    if ($decoded === false) {
        return false;
    }
    // ...
}
```

Standard PHP `base64_decode($value, true)` returns `false` if characters outside the Base64 alphabet (including whitespace or padding issues) are present. While Laravel's `Crypt` usually produces valid Base64, strict mode can be overly aggressive. More critically, relying on `base64_decode` return value alone is flaky for determining if something *is* encrypted content versus just random base64 data.

Also, casting to `array`/`json` on the Model complicates this logic (the trait attempts to decrypt first, but `parent::getAttribute` might have already cast the encrypted string to `null` or array if it wasn't valid JSON).

**Impact**
This can lead to `isEncrypted` returning `false` for valid encrypted strings, causing the trait to return the raw encrypted string instead of the decrypted value, or worse, attempting to use the raw string as if it were the value.

**To Reproduce**
1. Encrypt a string with `Crypt::encryptString`.
2. Ensure the resulting string contains characters that might trigger strict mode failure (rare with standard Base64 but possible with padding).
3. More likely: Cast a model attribute to `array` but store it encrypted. Access it. Observe `parent::getAttribute` failing or returning raw data because `isEncrypted` check or flow is flawed.

**Suggested Fix**
1. Use loose `base64_decode($value)` and verify the decoded structure is valid JSON with `iv`, `value`, `mac` keys (as done later in the function, but ensure the decode step itself isn't the blocker).
2. Rethink the interaction between `EncryptsAttributes` and Laravel's casting system. Ideally, decryption should happen *before* casting, or the cast should be aware of encryption.
