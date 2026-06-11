# Upgrading to v2

The full, detailed upgrade guide — including every breaking change, the step-by-step procedure, testing notes, and a final checklist — lives at the repository root:

**→ [UPGRADE.md](../UPGRADE.md)**

## Summary

| | 1.x | 2.0 |
| --- | --- | --- |
| PHP | 8.4+ | **8.5+** |
| Laravel | 12+ | **13+** |

```bash
composer require akira/laravel-sisp:^2.0
php artisan vendor:publish --tag=sisp-config --force
php artisan optimize:clear
```

**Breaking changes at a glance:**

1. PHP 8.5 / Laravel 13 minimums
2. Constructor signatures changed on `PaymentController`, `HandleCallbackAction`, `QueryTransactionStatusAction`, and `DeterminePaymentEndpointAction` (their `handle()` signatures are unchanged)
3. Callback fingerprint stubs must bind the `CallbackFingerprintValidator` contract instead of swapping the `Sisp` service
4. Package services are container-attribute bound — `app()->bound(...)` returns `false` before first resolution

Routes, schema, facade methods, models, events, value objects, and every 1.x config key are unchanged. New `driver` and `pipelines` config keys are optional with safe defaults.

See [Architecture](12-architecture.md) for the full v2 design.

**Previous:** [Architecture](12-architecture.md)
