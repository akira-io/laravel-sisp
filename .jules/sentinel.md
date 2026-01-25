## 2026-01-25 - Callback Validation Bypass
**Vulnerability:** Payment callback processing updated transaction status before validating the signature.
**Learning:** Code reuse (dispatching events) can obscure security checks if the order of operations isn't strictly enforced.
**Prevention:** Always validate signatures at the very entry point of the action/controller, before any state change.
