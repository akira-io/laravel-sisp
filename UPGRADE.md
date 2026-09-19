# Upgrading from 2.x to 3.0

Version 3.0 keeps the platform requirements of 2.x (**PHP 8.5**, **Laravel 13**). It hardens the callback, cancellation and refund paths, moves refund history into its own table and adds two cleanup commands. Four changes can break an application, and five new migrations must be published and run.

**Estimated effort:**

| Your usage profile | Effort |
| --- | --- |
| Routes, payment form, callbacks, events, facade only | Publish and run the new migrations |
| Parsing or storing the merchant reference or session | Review [the new reference format](#merchant-reference-and-session-format-action-required-if-you-parse-or-size-them) |
| Code that matches on `InvoiceStatus` or filters invoices by status | Review [the refunded invoice status](#refunded-invoice-status-action-required-if-you-match-or-filter-on-invoicestatus) |
| Calling `CancelTransactionAction` or `POST /sisp/refund/{transaction}` yourself | Review [cancellation](#cancellation-refuses-failed-and-refunded-transactions-action-required-if-you-cancel-them) and [refund validation](#refund-endpoint-validates-its-payload-action-required-if-you-call-it) |
| Instantiating package actions with `new` | Review the [constructor changes](#constructor-signatures-action-required-only-if-you-instantiate-with-new) |

---

## Breaking changes in 3.0

### Merchant reference and session format (action required if you parse or size them)

The default generators append ten random uppercase letters and digits to the timestamp, so a reference is no longer guessable and two payments started in the same second no longer collide:

| | 2.x | 3.0 |
| --- | --- | --- |
| `merchantReference` | `R20260523235959` (15 characters) | `R20260523235959K7M2QX9TBV` (25 characters) |
| `merchantSession` | `S20260523235959` (15 characters) | `S20260523235959K7M2QX9TBV` (25 characters) |

The package columns are `string` (255 characters), so they need no change. Update anything of your own that assumes 15 characters or parses the timestamp out of the value.

To keep the 2.x shape, point `sisp.generators` at your own class implementing `Akira\Sisp\Contracts\Generator`:

```php
namespace App\Sisp;

use Akira\Sisp\Contracts\Generator;

final readonly class LegacyMerchantReferenceGenerator implements Generator
{
    public function __invoke(): string
    {
        return 'R'.now()->format('YmdHis');
    }
}
```

```php
// config/sisp.php
'generators' => [
    'merchantSession' => App\Sisp\LegacyMerchantSessionGenerator::class,
    'merchantReference' => App\Sisp\LegacyMerchantReferenceGenerator::class,
    'timeStamp' => Akira\Sisp\Actions\Generators\TimeStampGeneratorAction::class,
],
```

Only do this if something outside the package requires it. With the 2.x shape both values are the second the payment started, and the session is the reference with `S` in place of `R`, so anyone can enumerate them. That matters for the cancellation callback, and less than it did for the result page:

- The customer cancellation callback carries no fingerprint. The reference and session are the only things identifying the transaction it cancels, so guessable values let a third party cancel pending payments.
- The POST callback's own redirect to the result page is signed in 3.0 (see [Payment result page links are signed](#payment-result-page-links-are-signed-action-required-if-you-link-to-it)), so the reference alone no longer opens it. The signed link still carries the reference in plain text, and anyone holding the link can open the page until it expires.

### Cancellation refuses failed and refunded transactions (action required if you cancel them)

`CancelTransactionAction::handle()` throws `LogicException` for transactions in `completed`, `cancelled`, `failed` or `refunded` status. In 2.x only `completed` and `cancelled` were refused, so a failed or refunded transaction could be moved to `cancelled`.

The action now reads the transaction back with `lockForUpdate()` inside a database transaction and runs the status check against the locked row, so two concurrent cancellations cannot both succeed. It also moves the linked invoice to `cancelled` in the same database transaction.

If you call the action yourself, catch `LogicException` for these statuses or check the status first.

### Refunded invoice status (action required if you match or filter on `InvoiceStatus`)

`InvoiceStatus` has a new `refunded` case. A full refund moves the invoice to `refunded` in the same database transaction as the transaction status; a partial refund leaves the transaction `completed` and the invoice untouched.

| Transaction event | Invoice status in 2.x | Invoice status in 3.0 |
| --- | --- | --- |
| Full refund | unchanged (normally `paid`) | `refunded` |
| Cancellation | unchanged | `cancelled` |

- An exhaustive `match` over `InvoiceStatus` without a `default` arm raises `UnhandledMatchError` on a refunded invoice. Add the `refunded` arm.
- A query or report counting `paid` invoices no longer includes fully refunded ones. Query `refunded` explicitly where you need them.

The `status` column is a plain string, so no data migration is required. Invoices refunded before the upgrade keep the status they had.

### Refund endpoint validates its payload (action required if you call it)

`RefundTransactionController::__invoke()` now receives `RefundTransactionRequest` instead of `Illuminate\Http\Request`. `POST /sisp/refund/{transaction}` validates:

| Field | Rules |
| --- | --- |
| `amount` | `required`, `numeric`, `gt:0` |
| `reason` | `sometimes`, `string`, `max:255` |

An invalid payload is rejected with **HTTP 422**:

```json
{
    "success": false,
    "message": "The refund request is invalid.",
    "errors": { "amount": ["..."] }
}
```

In 2.x the amount went through a `(float)` cast, so a missing or malformed amount either failed later with a 400 or 500, or was coerced into a different amount and refunded. Authorization is unchanged: the request still requires `$user->can('refund', $transaction)` and answers 403 otherwise. If you extended or called the controller directly, pass a `RefundTransactionRequest`.

### Constructor signatures (action required only if you instantiate with `new`)

Every `handle()` signature stays backward compatible: `MapTransactionStatusAction`, `Transaction\FailTransactionAction` and `Transaction\UpdateTransactionAttemptAction` gained an optional trailing parameter, and nothing else changed. Resolving these classes through the container (`app(...)`, `resolve(...)`, constructor injection) keeps working; only manual `new` calls need the new arguments.

| Class | 2.x constructor | 3.0 constructor |
| --- | --- | --- |
| `ValidatePaymentResponseFingerprintAction` | `PaymentResponseFingerPrintAction` | `PaymentResponseFingerPrintAction`, `PaymentErrorResponseFingerPrintAction` |
| `BuildSandboxPayloadAction` | `PaymentResponseFingerPrintAction`, `SispCredentialsResolver` | `PaymentResponseFingerPrintAction`, `PaymentErrorResponseFingerPrintAction`, `SispCredentialsResolver` |
| `CancelTransactionAction` | none | `UpdateInvoiceStatusAction` |
| `RefundTransactionAction` | `BuildRefundRequestAction` | `BuildRefundRequestAction`, `UpdateInvoiceStatusAction`, `RefundLedger` |
| `RenderPaymentResponseAction` | `GetPaymentErrorResponseAction`, `GetPaymentResponseTranslationsAction`, `CanRetryPaymentAction`, `InertiaAvailability` | `GetPaymentResponseTranslationsAction`, `CanRetryPaymentAction`, `InertiaAvailability` |
| `Transaction\UpdateTransactionAction` | `MapTransactionStatusAction`, `UpdateTransactionAttemptAction`, `ShouldPropagateAttemptCallbackAction` | the same, plus `ResolveCustomerErrorMessageAction`, `MaskCallbackRawPayloadAction` |
| `Transaction\FailTransactionAction` | `UpdateTransactionAttemptAction`, `ShouldPropagateAttemptCallbackAction` | the same, plus `ResolveCustomerErrorMessageAction`, `MaskCallbackRawPayloadAction` |
| `Transaction\UpdateTransactionAttemptAction` | none | `MaskCallbackRawPayloadAction` |
| `CallbackController` | `RenderPaymentResponseBasedOnConfigAction`, `StoreRequestMetadataAction`, `UpdateInvoiceStatusAction`, `LoadConfig` | `RenderPaymentResponseBasedOnConfigAction`, `StoreRequestMetadataAction`, `UpdateInvoiceStatusAction`, `CancelTransactionAction`, `LoadConfig`, `CallbackFingerprintValidator`, `BuildPaymentResultUrlAction` |
| `CancelTransactionController` | `CancelTransactionAction` | `CancelTransactionAction`, `BuildPaymentResultUrlAction` |
| `Pipelines\Callback\Pipes\ValidateFingerprint` | `CallbackFingerprintValidator`, `FailTransactionAction` | `CallbackFingerprintValidator` |

```php
// 2.x
new ValidatePaymentResponseFingerprintAction($successFingerprint);
new BuildSandboxPayloadAction($successFingerprint, $resolver);

// 3.0
new ValidatePaymentResponseFingerprintAction($successFingerprint, $errorFingerprint);
new BuildSandboxPayloadAction($successFingerprint, $errorFingerprint, $resolver);
```

`CallbackPayload` gained constructor parameters too, all optional and appended after the existing ones, so existing calls keep compiling.

### A forged callback no longer fails the transaction (action required if you relied on `invalid_callback_fingerprint`)

In 2.x a callback whose fingerprint did not match moved the transaction to `failed`, cancelled its invoice, dispatched `PaymentFailed`, stored the sender's transaction id, message type and fingerprint, and cleared the SISP refusal fields. Anyone who knew a merchant reference and session could do that to a payment that was not yet completed.

In 3.0 the callback controller checks the fingerprint before the pipeline, logs a warning and redirects to `sisp.redirect_url`. The transaction, its attempt, its invoice and the request metadata are left alone, and no event is dispatched. The `ValidateFingerprint` pipe behaves the same way for code that runs `HandleCallbackPipeline` directly: it short-circuits with the failure reason `invalid_callback_fingerprint` and writes nothing. The controller only runs this check while `ValidateFingerprint` is in `sisp.pipelines.callback`; if you replaced it, your own pipes decide.

If you listened for `PaymentFailed` or searched for `merchant_response = 'invalid_callback_fingerprint'` to detect forged callbacks, watch the log for `SISP callback rejected: the fingerprint does not match.` instead.

### Payment result page links are signed (action required if you link to it)

The POST callback and the signed `/sisp/cancel` route now redirect to a temporary signed URL for `GET /sisp/callback?ref=<merchantReference>`, valid for 30 minutes. The GET route renders the result page only for a valid signature and redirects to `sisp.redirect_url` otherwise. Links you build yourself must be signed the same way:

```php
use Akira\Sisp\Actions\BuildPaymentResultUrlAction;

return redirect(app(BuildPaymentResultUrlAction::class)->handle($transaction));
```

Customers who bookmark the result page are redirected to `sisp.redirect_url` once the link expires. The Inertia `transaction` prop no longer carries `merchant_session`; together with the reference it was enough to cancel a pending payment through the unsigned cancellation callback. Read it from your own records if a page needs it.

---

## Database migrations (action required)

3.0 ships five new migrations. Like every migration in this package they are published, not loaded automatically, so they only run once you publish them:

| Migration | What it does |
| --- | --- |
| `update_laravel_sisp_transactions_add_callback_error_fields` | Adds `callback_raw_payload`, `error_code` and `error_message` to the transactions table. The callback writes these columns, so this migration must run before 3.0 serves callbacks. |
| `create_sisp_refunds_table` | Creates the refunds table (`sisp.tables.refunds`, default `sisp_refunds`) and copies the refund history stored in each transaction's `payload['refunds']` into it. The copy skips transactions already present in the table, so it is safe to re-run after an interruption. Transactions whose payload cannot be read are listed in a `SISP refund history could not be read for some transactions.` warning in the log. |
| `update_laravel_sisp_transactions_add_status_created_at_index` | Adds an index on `status` and `created_at`, used by `sisp:expire-pending` and `sisp:prune-request-payloads`. |
| `update_laravel_sisp_transactions_add_request_payload_pruned_at` | Adds the `request_payload_pruned_at` column that `sisp:prune-request-payloads` uses to track its progress. |
| `update_sisp_refunds_add_idempotency_key` | Adds a nullable `idempotency_key` column to the refunds table and a unique index on `transaction_id` and `idempotency_key`. It also gives `transaction_id` an index of its own, so the foreign key does not depend on the unique index. It must run after `create_sisp_refunds_table` and before 3.0 takes refunds: 3.0 writes the column on every refund, keyed or not. |

```bash
php artisan vendor:publish --tag=sisp-migrations
php artisan migrate
```

Publishing keeps the migrations you already published under their original file names and only adds the new ones.

Plan the deploy around these points:

- **Run `migrate` before 3.0 serves requests.** 3.0 writes the new columns on every callback and the refunds table on every refund, and fails until they exist. A refund that a still-running 2.x release records while the copy runs lands only in `payload['refunds']`; 3.0 counts the larger of the refunds table and the payload, so that refund is still counted and cannot be refunded twice.
- **Large transactions tables take time and locks.** The refunds copy reads every transaction, payload included, and writes one row per refund; on PostgreSQL and SQLite the whole migration runs in one database transaction, so an interrupted run starts over. The index on `status` and `created_at` is created without `CONCURRENTLY`, which blocks writes to the transactions table on PostgreSQL while it builds, and on MySQL before 8.0.29 the new columns rebuild the table. Run it in a low-traffic window, or create the `status`/`created_at` index yourself beforehand: the migration skips an index that already exists.
- **Back up before rolling back.** Rolling back `update_laravel_sisp_transactions_add_callback_error_fields` drops `error_code`, `error_message` and `callback_raw_payload` for good. Rolling back the refunds table loses nothing, because every refund is still mirrored in `payload['refunds']`. Rolling back `update_sisp_refunds_add_idempotency_key` drops the stored keys, so a retry sent after the rollback with a key used before it is refunded again.

The refund history is still appended to `payload['refunds']` as well, so code reading it keeps working. New code should read `$transaction->refunds()` (`Akira\Sisp\Models\Refund`).

---

## New commands (opt-in)

Two cleanup commands ship with 3.0. Neither is scheduled by the package; register the ones you want in `routes/console.php`.

| Command | What it does | Default window |
| --- | --- | --- |
| `sisp:expire-pending` | Cancels `pending` transactions that never received a callback, with the reason `expired`. `--older-than` must be at least `1`. | 30 days (`SISP_EXPIRE_PENDING_AFTER_DAYS`) |
| `sisp:prune-request-payloads` | Removes the 3-D Secure `purchaseRequest` blob from `completed`, `failed`, `cancelled` and `refunded` transactions. | 90 days (`SISP_PRUNE_REQUEST_PAYLOADS_AFTER_DAYS`) |

Both accept `--older-than=<days>` and `--limit=<count>` (default 100 per run). Every numeric option of the package's commands, including `sisp:reconcile-pending` and `sisp:regenerate-pdfs`, must be a whole number; anything else, or a value below the minimum, fails the command instead of processing nothing or everything.

When `sisp.transaction_status.reconciliation_enabled` is on, `sisp:expire-pending` asks SISP for each transaction's status before cancelling it. A transaction SISP reports as paid or refused is completed or failed instead, and one SISP cannot be asked about stays `pending` until the next run.

```php
// routes/console.php
use Illuminate\Support\Facades\Schedule;

Schedule::command('sisp:expire-pending')->weekly();
Schedule::command('sisp:prune-request-payloads')->monthly();
```

If you maintain a customized `config/sisp.php`, the new keys are optional and fall back to the defaults above:

```php
'tables' => [
    // ...
    'refunds' => env('SISP_TABLE_REFUNDS', 'sisp_refunds'),
],

'expire_pending_after_days' => env('SISP_EXPIRE_PENDING_AFTER_DAYS', 30),

'prune_request_payloads_after_days' => env('SISP_PRUNE_REQUEST_PAYLOADS_AFTER_DAYS', 90),

'middleware' => [
    // ...
    'callback' => ['throttle:sisp-callback'],
],
```

See [docs/09-troubleshooting.md](docs/09-troubleshooting.md#cleanup-commands) for the rationale behind each window.

---

## Behavioral notes

- **`TransactionCancelled` fires in more places.** In 2.x the customer cancellation callback (`UserCancelled`) only redirected and left the transaction `pending`. In 3.0 it cancels the matching pending transaction through `CancelTransactionAction`, which dispatches `TransactionCancelled` with the reason `user_cancelled`. `sisp:expire-pending` dispatches it too, with the reason `expired`. The signed `/sisp/cancel` route also defaults to `user_cancelled`, so a listener cannot tell it apart from the callback by the reason alone. Listeners written for the explicit cancellation path (the signed `/sisp/cancel` route or your own calls to `CancelTransactionAction`) now also run for these two.
- **The cancellation callback requires both identifiers.** It only cancels a `pending` transaction matching a non-empty `merchantRef` and `merchantSession`. The callback route now carries the `throttle:sisp-callback` middleware, which limits cancellation callbacks (`UserCancelled` or `userCancelled`) to 10 per minute per merchant reference and 30 per minute per IP address; override it with `sisp.middleware.callback`. Behind a load balancer, configure `TrustProxies` so customers do not share one address bucket. Do not rely on it to protect guessable references.
- **Status comes from the documented message type table.** Only `messageType = 6` fails a transaction. A known success message type completes it only when `merchantResp` has the expected value; otherwise the transaction stays `pending` and a warning is logged.
- **Refused callbacks keep their reason.** The error fingerprint formula validates SISP refusals, and the refusal code and message are stored in `error_code` and `error_message` and shown on the response screen. The raw callback is stored encrypted in `callback_raw_payload`, with the card number masked to its last four digits, and encrypted attributes are redacted in `sisp_transaction_logs`.
- **Refunds accept an idempotency key.** `RefundTransactionAction::handle()` takes an optional fourth argument `?string $idempotencyKey`, `RefundBuilder` has `idempotencyKey()`, and `POST /sisp/refund/{transaction}` accepts `idempotency_key`. A retry with the same key and amount returns the transaction without refunding again or dispatching `TransactionRefunded`; the same key with another amount is refused with `LogicException` (400 on the route). Calls without a key behave as before, so a client that retries after a timeout without a key can still refund twice. See [docs/05-transaction-management.md](docs/05-transaction-management.md#idempotent-refunds).
- **Checkout keys no longer stick in `processing`.** A payment intent left in `processing` by a request that died mid-flight is reclaimed once it has not changed for `sisp.idempotency.processing_timeout_seconds` (600 by default; 0 never reclaims). A published 2.x config lacks the key and gets the default. When the pipeline fails after the transaction was stored, the failed intent keeps its `transaction_id`, and the next request with the key reuses that transaction.
- **Card numbers in request metadata are masked.** `sisp_request_metadata.custom_metadata` keeps only the last four digits of `merchantRespPan`. Rows stored before the upgrade are not rewritten.
- **Refunds settle below a centavo.** `refundableAmount()` (on the model and on `RefundTransactionAction`) returns 0 unless the transaction is `completed`, and a remaining balance below 0.01 counts as settled: the refund that reaches it moves the transaction and its invoice to `refunded`. A transaction left `completed` with such a residue by an earlier release offers nothing more to refund; close it by setting its status to `refunded`.
- **Refund history on the model.** `Transaction` gains `refundedAmount()`, `refundableAmount()` and `isPartiallyRefunded()`, read from the same ledger as `RefundTransactionAction`. `TransactionRefunded` carries the recorded `Refund` and the remaining balance in `$refund` and `$remainingAmount`.
- **Callbacks apply once.** The callback actions lock the transaction and its attempt. A replayed callback, or one for a transaction already `completed` or `refunded`, changes nothing on the transaction and dispatches no event; a late callback is still recorded on its attempt.
- **Deprecations.** `ErrorMessageType` and `GetPaymentErrorResponseAction` are deprecated and no longer used by the package. They remain for published views that read the old error array shape.

---

## Upgrade steps

In your application repository:

```bash
composer require akira/laravel-sisp:^3.0
php artisan vendor:publish --tag=sisp-migrations
```

Commit the updated `composer.lock` and the published migrations. Then deploy with the application in maintenance mode, so neither the old nor the new code serves requests while the schema changes:

```bash
php artisan down
composer install --no-dev
php artisan migrate --force
php artisan optimize:clear
php artisan up
```

Finally, run a sandbox payment end to end (`SISP_SANDBOX=true`), one refused payment and one customer cancellation, and confirm each transaction and invoice ends in the expected status.

---

## Checklist

- [ ] `composer require akira/laravel-sisp:^3.0`
- [ ] Migrations published and run inside a maintenance window, with a backup taken first
- [ ] Nothing of your own assumes a 15-character merchant reference or session
- [ ] `match` expressions and invoice queries handle `InvoiceStatus::refunded`
- [ ] Direct calls to `CancelTransactionAction` handle `LogicException` for `failed` and `refunded` transactions
- [ ] Clients of `POST /sisp/refund/{transaction}` handle a 422 response
- [ ] Clients that retry `POST /sisp/refund/{transaction}` send an `idempotency_key`
- [ ] No manual `new` instantiation of the classes in the constructor table
- [ ] `TransactionCancelled` listeners reviewed for the callback and `expire-pending` paths
- [ ] `sisp:expire-pending` and `sisp:prune-request-payloads` scheduled, if you want them
- [ ] Sandbox payment, refusal and cancellation verified end to end

---

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
