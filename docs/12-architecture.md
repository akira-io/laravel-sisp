# Architecture (v2)

Version 2 restructures the package around four explicit, composable patterns. The public API of v1 (`Sisp` facade, `ScopedSisp`, routes, events, models) is preserved; the internals are now organized for extension.

## Requirements

- PHP 8.5+
- Laravel 13+

## Overview

```
HTTP / Facade
    |
    v
Builders ──────────► Value Objects (PaymentRequestData, PaymentRequest, ...)
    |
    v
Pipelines (payment / callback)
    |        each pipe delegates to an
    v
Actions (single-purpose, final, constructor-injected)
    |        gateway interactions go through the
    v
Drivers (production / sandbox / custom) ── SispManager
```

## Builder Pattern

`Akira\Sisp\Builders` provides fluent composition of requests:

- `PaymentBuilder` — `Sisp::payment()->amount(...)->customerEmail(...)->build()`
- `RefundBuilder` — `Sisp::refund($transaction)->full()->process()`

Builders validate their inputs (`LogicException` on missing amount) and delegate to the same actions used by the HTTP flow, so behavior is identical regardless of entry point.

## Driver Pattern

`Akira\Sisp\Drivers\SispManager` extends `Illuminate\Support\Manager` and resolves the gateway driver behind the `Akira\Sisp\Contracts\SispDriver` contract:

- `ProductionDriver` — payment endpoint from the resolved credentials' URL; transaction-status queries through the SISP POS API (`TransactionStatusClient`)
- `SandboxDriver` — payment endpoint at the local fake gateway route (`sisp.sandbox`)

Selection order: `config('sisp.driver')` → credentials' `sandbox` flag. Custom gateways register with `SispManager::extend()` and are selected with `SISP_DRIVER`. Multi-merchant flows keep working: drivers resolve credentials lazily through `SispCredentialsResolver`, so `Sisp::forCredentials()` scoping applies to driver calls too.

## Pipeline Pattern

Both processing flows are `Illuminate\Pipeline\Pipeline` runs over a mutable context object. Stages are small, final pipe classes, configured in `config('sisp.pipelines')`:

- **Payment** — `ProcessPaymentPipeline` over `PaymentContext` (`EnsureIpIsNotBlacklisted` → `EnforceRateLimits` → `BuildPaymentRequest` → `PersistTransaction` → `CaptureRequestMetadata`)
- **Callback** — `HandleCallbackPipeline` over `CallbackContext` (`ResolveTransaction` → `ValidateFingerprint` → `EnsureCallbackMatchesTransaction` → `ApplyTransactionStatus` → `DispatchPaymentEvents`)

Failure semantics: callback pipes that detect tampering or mismatches mark the transaction `failed`, dispatch `PaymentFailed`, record the reason on the context (`failureReason`), and short-circuit the remaining pipes — exactly the behavior of v1, now in isolated, replaceable units.

## Actions Pattern

Every unit of work remains a dedicated, `final readonly`, constructor-injected class with a single `handle()` method under `Akira\Sisp\Actions`. Pipes and builders delegate to actions; actions never reach back into controllers or pipelines.

## SOLID Boundaries

- **Single responsibility** — one pipe/action per concern; gateway HTTP isolated in `TransactionStatusClient`
- **Open/closed** — pipelines and the driver manager extend through configuration, not modification
- **Liskov** — drivers are interchangeable behind `SispDriver`
- **Interface segregation** — narrow contracts (`PaymentPipe`, `CallbackPipe`, `CallbackFingerprintValidator`, `SispCredentialsResolver`)
- **Dependency inversion** — pipes depend on contracts; tests and applications swap implementations through the container

## Laravel 13 Native Syntax

The package uses framework attributes throughout:

| Where | Attributes |
| --- | --- |
| Eloquent models | `#[Fillable]`, `#[UseFactory]`, `#[Scope]`, `casts()` method |
| Console commands | `#[Signature]`, `#[Description]` |
| Contracts/services | `#[Bind]`, `#[Singleton]` container attributes |
| Overridden properties | PHP 8.5 `#[\Override]` attribute |

The service provider's `register()` is reduced to the single driver-contract closure; everything else is declared where it lives. Note: `#[Bind]` requires the container's environment resolver, which full applications register during bootstrap — the provider mirrors it for lighter harnesses such as Testbench.

**Previous:** [API Reference](11-api-reference.md) | **Next:** [Upgrade Guide](13-upgrade-v2.md)
