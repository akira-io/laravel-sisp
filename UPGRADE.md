# Upgrading from 1.x to 2.0

This guide walks existing installations through the upgrade to v2, which targets **Laravel 13** and **PHP 8.5** and reorganizes the package internals around builders, drivers, and pipelines.

**Estimated effort:**

| Your usage profile | Effort |
| --- | --- |
| Routes, payment form, callbacks, events, facade only | Platform upgrade only — no code changes |
| Custom config (middleware, views, invoice settings) | Platform upgrade + re-publish config |
| Resolving package actions directly from the container | Review the [internal changes](#3-internal-api-changes) |
| Mocking/extending package internals in tests | Review the [testing notes](#4-testing-changes) |

---

## 1. Breaking changes

### 1.1 Platform requirements (action required)

| | 1.x | 2.0 |
| --- | --- | --- |
| PHP | >= 8.4 | **>= 8.5** |
| Laravel (`illuminate/contracts`) | ^12.0 \|\| ^13.0 | **^13.0** |
| orchestra/testbench (dev) | ^10.0 \|\| ^11.0 | **^11.0** |

Upgrade your application to Laravel 13 and PHP 8.5 first, then:

```bash
composer require akira/laravel-sisp:^2.0
```

### 1.2 Constructor signatures of public actions (action required if you resolve them with custom arguments)

The following actions kept their **`handle()` signatures and behavior**, but their constructors changed. If you resolve them through the container (`app(...)`, `resolve(...)`, constructor injection) nothing breaks. If you instantiate them manually with `new`, update the arguments:

| Class | 1.x constructor | 2.0 constructor |
| --- | --- | --- |
| `PaymentController` | 6 actions | `ProcessPaymentPipeline`, `RenderPaymentFormBasedOnConfigAction` |
| `HandleCallbackAction` | 4 dependencies | `HandleCallbackPipeline` |
| `QueryTransactionStatusAction` | `LoadConfig`, `SispCredentialsResolver` | `SispManager` |
| `DeterminePaymentEndpointAction` | `SispCredentialsResolver` | `SispManager` |

### 1.3 Callback fingerprint validation moved behind a contract (action required if you stubbed it)

In 1.x, `HandleCallbackAction` validated callbacks through the `Sisp` facade, so test suites could influence the result by swapping the `Sisp` service:

```php
// 1.x — no longer works
app()->instance(\Akira\Sisp\Sisp::class, new class {
    public function validateCallback($payload): bool { return true; }
});
```

In 2.0 the callback pipeline depends on the `CallbackFingerprintValidator` contract. Bind your stub on the contract instead:

```php
// 2.0
use Akira\Sisp\Contracts\CallbackFingerprintValidator;
use Akira\Sisp\ValueObjects\CallbackPayload;

app()->instance(CallbackFingerprintValidator::class, new class implements CallbackFingerprintValidator {
    public function handle(CallbackPayload $payload): bool { return true; }
});
```

### 1.4 Container bindings are attribute-based (action required only for `bound()` checks)

`LoadConfig`, `SispManager`, `Sisp`, `SispCredentialsResolver`, and `CallbackFingerprintValidator` are no longer registered eagerly in the service provider. They are declared with Laravel 13 container attributes (`#[Bind]` on the contracts, `#[Singleton]` on the services) and registered on first resolution.

- **Overriding still works the same way:** explicit bindings in your application's service provider take precedence over attributes.
- **Behavior difference:** `app()->bound(SispCredentialsResolver::class)` now returns `false` until the contract has been resolved at least once. If you guarded code with `bound()` checks, resolve the contract directly instead.

### 1.5 Eloquent model configuration moved to attributes (informational)

Models now declare `#[Fillable]` and `#[UseFactory]` class attributes and a `casts()` method instead of `$fillable`/`$casts` properties. The runtime behavior (`getFillable()`, casting, factories) is identical, and all models remain `final` — this only matters if you reflect on those properties directly.

---

## 2. Upgrade steps

```bash
# 1. Update the dependency
composer require akira/laravel-sisp:^2.0

# 2. Re-publish the config to pick up the new keys (or merge manually, see below)
php artisan vendor:publish --tag=sisp-config --force

# 3. Clear caches
php artisan optimize:clear
```

If you maintain a customized `config/sisp.php`, merge these new keys instead of force-publishing:

```php
// Gateway driver: null (auto from sandbox flag), 'production', 'sandbox', or custom
'driver' => env('SISP_DRIVER'),

// Processing pipelines: reorder, remove, or append your own pipes
'pipelines' => [
    'payment' => [
        Akira\Sisp\Pipelines\Payment\Pipes\EnsureIpIsNotBlacklisted::class,
        Akira\Sisp\Pipelines\Payment\Pipes\EnforceRateLimits::class,
        Akira\Sisp\Pipelines\Payment\Pipes\BuildPaymentRequest::class,
        Akira\Sisp\Pipelines\Payment\Pipes\PersistTransaction::class,
        Akira\Sisp\Pipelines\Payment\Pipes\CaptureRequestMetadata::class,
    ],
    'callback' => [
        Akira\Sisp\Pipelines\Callback\Pipes\ResolveTransaction::class,
        Akira\Sisp\Pipelines\Callback\Pipes\ValidateFingerprint::class,
        Akira\Sisp\Pipelines\Callback\Pipes\EnsureCallbackMatchesTransaction::class,
        Akira\Sisp\Pipelines\Callback\Pipes\ApplyTransactionStatus::class,
        Akira\Sisp\Pipelines\Callback\Pipes\DispatchPaymentEvents::class,
    ],
],
```

Both keys are optional: when absent, the package falls back to the defaults shown above, so an unmodified 1.x config file keeps working.

Finally, run a sandbox payment end to end (`SISP_SANDBOX=true`) and confirm the transaction completes and the invoice is marked paid.

---

## 3. Internal API changes

### What stays exactly the same

- All HTTP routes, form fields, middleware configuration, and views
- The `Sisp` facade methods and the `ScopedSisp` multi-merchant API
- Models, relationships, value objects, enums, events, and exceptions
- Database schema — **no new migrations**
- All actions keep their names and `handle()` signatures
- Every 1.x configuration key (v2 only adds keys)

### New extension points (optional adoption)

| Need | v2 API |
| --- | --- |
| Compose a payment in code | `Sisp::payment()->amount(...)->customerEmail(...)->build()` |
| Refund fluently | `Sisp::refund($transaction)->full()->reason(...)->process()` |
| Add a step to the payment/callback flow | Implement `PaymentPipe`/`CallbackPipe`, register in `sisp.pipelines` |
| Point to a different gateway | Implement `SispDriver`, register with `SispManager::extend()`, set `SISP_DRIVER` |
| Replace fingerprint validation | Bind `CallbackFingerprintValidator` |
| Resolve the active gateway | `Sisp::driver()` or inject the `SispDriver` contract |

The 1.x style (resolving actions directly) keeps working — builders and pipes delegate to the same action classes.

---

## 4. Testing changes

- **Fingerprint stubs:** bind `CallbackFingerprintValidator` (see [1.3](#13-callback-fingerprint-validation-moved-behind-a-contract-action-required-if-you-stubbed-it)).
- **Custom harnesses:** `#[Bind]` container attributes require an environment resolver. Full Laravel apps and this package's service provider register it automatically; if you boot the container manually, call `$app->resolveEnvironmentUsing(...)`.
- **Factories:** models declare `#[UseFactory]`, so `Factory::guessFactoryNamesUsing()` workarounds for SISP models can be removed.

---

## 5. Behavioral notes (no action needed)

These are fixes and clarifications shipped in 2.0 — listed so nothing surprises you in production:

- **Invoice PDF generation can no longer fail the payment callback.** In 1.x, an exception while rendering the PDF (missing headless browser, storage failure) returned HTTP 500 to SISP *after* the transaction had already completed. In 2.0 the error is logged (`SISP invoice PDF generation failed.`) and the callback responds normally; recover missing PDFs with `php artisan sisp:regenerate-pdfs`.
- **Invoices render for buyers without optional customer data.** Missing name/email/address no longer raise a `TypeError` during PDF generation.
- **Retrying a payment** (`POST /sisp/retry-payment`) resets the transaction to `pending` with a rotated merchant session — same as late 1.x, now covered by tests.
- **Refunds require the original callback data** (`transaction_id` and `response_code`), as SISP mandates — same as late 1.x, now covered by tests.

---

## 6. Quick checklist

- [ ] Application on PHP 8.5 and Laravel 13
- [ ] `composer require akira/laravel-sisp:^2.0`
- [ ] Config re-published or new `driver`/`pipelines` keys merged
- [ ] No manual `new` instantiation of `PaymentController`, `HandleCallbackAction`, `QueryTransactionStatusAction`, `DeterminePaymentEndpointAction`
- [ ] Test stubs of `Sisp::validateCallback` migrated to the `CallbackFingerprintValidator` contract
- [ ] `app()->bound(...)` checks on package contracts replaced with direct resolution
- [ ] Sandbox payment flow verified end to end

For the full v2 design, see [docs/12-architecture.md](docs/12-architecture.md).
