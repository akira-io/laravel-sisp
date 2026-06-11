# Upgrading to v2

v2 targets Laravel 13 and PHP 8.5 and reorganizes the internals around builders, drivers, and pipelines. The public surface is backward compatible for typical integrations.

## Requirements

| | v1 | v2 |
| --- | --- | --- |
| PHP | 8.4+ | **8.5+** |
| Laravel | 12+ | **13+** |
| Testbench (dev) | 10/11 | **11** |

```bash
composer require akira/laravel-sisp:^2.0
```

## What stays the same

- All routes, controllers, form fields, and views
- The `Sisp` facade methods and `ScopedSisp` multi-merchant API
- Models, value objects, enums, events, exceptions, and database schema
- Actions keep their names and `handle()` signatures
- Configuration keys from v1 (new keys were only added)

## What changed

### New configuration keys

```php
// config/sisp.php
'driver' => env('SISP_DRIVER'),     // null (auto), production, sandbox, custom
'pipelines' => [
    'payment' => [/* pipe class-strings */],
    'callback' => [/* pipe class-strings */],
],
```

Re-publish the config file or merge these keys manually:

```bash
php artisan vendor:publish --tag=sisp-config --force
```

### Behavior notes

- `DeterminePaymentEndpointAction` and `QueryTransactionStatusAction` now delegate to the active driver via `SispManager`. Behavior is unchanged for the built-in `production`/`sandbox` modes.
- `HandleCallbackAction` keeps its `handle(CallbackPayload): Transaction` signature but runs the callback pipeline internally.
- `PaymentController` runs the payment pipeline; the same checks run in the same order as v1.
- Retrying a payment (`POST /sisp/retry-payment`) resets the transaction to `pending` with a rotated merchant session (formalized v1 behavior).
- Refunds require the original callback to have stored `transaction_id` and `response_code` (clearing period), as SISP mandates.

### If you extended internals

- Code that mocked `Sisp::validateCallback()` to influence callback handling should now bind a custom `Akira\Sisp\Contracts\CallbackFingerprintValidator` instead.
- Container bindings are declared with `#[Bind]`/`#[Singleton]` attributes. Overriding still works the standard way: register your own binding in a service provider (explicit bindings take precedence over attributes).
- If you run the package under a custom test harness and the contracts fail to resolve, ensure an environment resolver is registered (`$app->resolveEnvironmentUsing(...)`); full Laravel applications and this package's provider already do this.

## New capabilities you can adopt

```php
// Fluent payment composition
$request = Sisp::payment()->amount(1500.0)->customerEmail('a@b.cv')->build();

// Fluent refunds
Sisp::refund($transaction)->full()->reason('customer_request')->process();

// Custom gateway drivers
resolve(SispManager::class)->extend('custom', fn () => new CustomDriver());

// Custom pipeline stages
config(['sisp.pipelines.payment' => [...default pipes..., App\Sisp\Pipes\MyPipe::class]]);
```

See [Architecture](12-architecture.md) for the full design.

**Previous:** [Architecture](12-architecture.md)
