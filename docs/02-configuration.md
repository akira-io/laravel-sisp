# Configuration Guide

All configuration is done through the `config/sisp.php` file or environment variables in `.env`.

## Required Configuration

### SISP Credentials

```env
SISP_URL=https://mc.vinti4net.cv/Client_VbV_v2/biz_vbv_clientdata.jsp
SISP_POS_ID=your_pos_id
SISP_POS_AUT_CODE=your_authorization_code
SISP_MERCHANT_ID=your_merchant_id
```

These credentials are provided by SISP when you register as a merchant.

### Currency and Language

```env
SISP_CURRENCY=132              # 132 = CVE (Cape Verde Escudo)
SISP_LANGUAGE_MESSAGES=en      # en or pt
```

The locale for each transaction is automatically stored in the `locale` field of the transaction record. This allows you to track and filter transactions by customer language preference.

## Optional Configuration

### Generators

The package resolves generator classes through the Laravel container and invokes them with no arguments:

```php
'generators' => [
    'merchantSession' => App\Sisp\GenerateMerchantSession::class,
    'merchantReference' => App\Sisp\GenerateMerchantReference::class,
    'timeStamp' => App\Sisp\GenerateTimeStamp::class,
],
```

Each generator must be an invokable class that returns a string. The `timeStamp` generator must return the SISP timestamp format `Y-m-d H:i:s`, for example `2026-05-26 14:30:00`. If a configured generator cannot be resolved or invoked, Laravel raises an exception while building the payment request.

### Retry Payments

```env
SISP_ALLOW_RETRY=true
```

Retry links are temporary signed URLs. The retry endpoint rejects unsigned, expired, or tampered requests before resolving the transaction.

### 3D Secure

```env
SISP_IS_3D_SEC=1               # 1 = enabled, 0 = disabled
SISP_TRANSACTION_CODE=1        # Transaction type
```

When 3D Secure is enabled, the payment request requires customer data to build
the `purchaseRequest` payload. If any required field is missing, the request
throws `MissingThreeDSecureDataException`.

Required fields when `SISP_IS_3D_SEC=1`:
- `customer_email`
- `customer_country` (ISO alpha-2, e.g., PT)
- `customer_city`
- `customer_address`
- `customer_postal_code`

Optional:
- `customer_phone`

### Sandbox Mode

```env
SISP_SANDBOX=true              # true for testing, false for production
```

When enabled, uses the fake gateway instead of real SISP server for testing.

### Gateway Driver (v2)

```env
SISP_DRIVER=                   # null (auto), production, sandbox, or a custom driver
```

Gateway interactions are routed through a driver resolved by `Akira\Sisp\Drivers\SispManager`. When `SISP_DRIVER` is empty, the driver is derived from the resolved credentials: `sandbox` when sandbox mode is enabled, `production` otherwise. Setting it explicitly overrides the sandbox flag.

Custom drivers implement `Akira\Sisp\Contracts\SispDriver` and are registered in a service provider:

```php
use Akira\Sisp\Drivers\SispManager;

resolve(SispManager::class)->extend('custom', fn () => new CustomDriver());
```

```env
SISP_DRIVER=custom
```

### Processing Pipelines (v2)

The payment and callback flows run through Laravel pipelines. Each stage is a single-purpose pipe class, configured in `config/sisp.php`:

```php
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

You can reorder, remove, or append pipes. Payment pipes implement `Akira\Sisp\Contracts\PaymentPipe`; callback pipes implement `Akira\Sisp\Contracts\CallbackPipe`. See [Payment Flow](./04-payment-flow.md) for the responsibilities of each pipe.

## Invoice Configuration

### Company Information

```env
SISP_COMPANY_NAME="Your Company"
SISP_COMPANY_ADDRESS="Street Address, City"
SISP_COMPANY_CODE="VAT123456"
SISP_COMPANY_EMAIL="billing@company.com"
SISP_COMPANY_COUNTRY="CV"
SISP_COMPANY_PHONE="+238 XXXXXXX"
SISP_COMPANY_WEBSITE="https://company.com"
```

### Invoice Settings

```env
SISP_INVOICE_TEMPLATE=modern       # modern, minimal, or branded
SISP_INVOICE_NUMBER_FORMAT=date-based
SISP_INVOICE_NUMBER_PREFIX=INV
SISP_INVOICE_DISK=public           # Storage disk for PDFs
SISP_INVOICE_PATH=invoices         # Directory inside the disk for PDFs
```

`SISP_INVOICE_PATH` is relative to the configured filesystem disk. With the default `public` disk it resolves under `storage/app/public`. With S3 it is used as the object key prefix.

## Rendering Engine Configuration

### Blade Views (Default)

```env
SISP_USE_BLADE=true
SISP_USE_INERTIA=false
```

Renders payment forms using Blade templates.

Package Blade views use the package-scoped `<x-sisp::layouts.app>` layout by default, so they do not require the host application to provide a `layouts.app` view. If you publish the views for customization, keep the package layout component or replace it with your own explicit application layout.

### Inertia.js

```env
SISP_USE_INERTIA=true
SISP_USE_BLADE=false
SISP_INERTIA_PAYMENT_COMPONENT=sisp/payment-form
SISP_INERTIA_CALLBACK_COMPONENT=sisp/payment-response
```

For React or Vue 3 frontends. Choose ONE framework and install it:

```bash
npm install @inertiajs/react    # For React
# or
npm install @inertiajs/vue3     # For Vue 3
```

## Transaction Status Checks

SISP provides three official ways to check payment status:

- Programmatic POS API: use when the payment gateway times out after about 5 minutes or when no automatic callback was received.
- Merchant Portal: manual lookup for support teams.
- Daily VBVT reconciliation file: accounting/bulk reconciliation after midnight for the previous day.

Configure the POS transaction-status API:

```env
SISP_TRANSACTION_STATUS_URL=https://comerciante.vinti4.cv/pos/transaction-status
SISP_PORTAL_ID=your_portal_or_application_id
SISP_PORTAL_PASSWORD=your_portal_password
SISP_TRANSACTION_STATUS_TIMEOUT=10
SISP_TRANSACTION_RECONCILIATION_ENABLED=false
SISP_TRANSACTION_RECONCILE_AFTER_MINUTES=5
SISP_TRANSACTION_RECONCILE_LIMIT=50
```

The default URL is the production endpoint. For the SISP test environment, set `SISP_TRANSACTION_STATUS_URL=https://comerciante.teste.sisp.cv/pos/transaction-status`.

The API sends HTTP Basic authentication using `SISP_PORTAL_ID:SISP_PORTAL_PASSWORD` and posts JSON with `posID`, `posAuthCode`, and `merchantRef`.

### Manual Status Query

Query a transaction without changing local data:

```bash
php artisan sisp:transaction-status R20260523235959
```

Query by local transaction ID and update it only when SISP returns a successful status API result:

```bash
php artisan sisp:transaction-status --transaction=123 --update
```

`result=false` means the status API request itself failed and should not be treated as a definitive payment failure. `result=true` with `transactionSuccess=true` maps to `completed`; `result=true` with `transactionSuccess=false` maps to `failed`.

### Public Status API

Use the facade when application code needs a one-off status check or wants to reconcile a known transaction inside a domain workflow:

```php
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;

$response = Sisp::queryTransactionStatus($transaction);
$response = Sisp::queryTransactionStatus('R20260523235959');

$updatedTransaction = Sisp::reconcileTransactionStatus($transaction);
```

`queryTransactionStatus()` returns `TransactionStatusResponse` and does not change local data. `reconcileTransactionStatus()` updates only pending transactions when SISP returns `result=true`.

For multi-merchant applications, pass explicit credentials:

```php
$sisp = Sisp::forCredentials($credentials);

$response = $sisp->queryTransactionStatus($transaction);
$updatedTransaction = $sisp->reconcileTransactionStatus($transaction);
```

Use the Artisan commands for operations and scheduled monitoring. Use the public API when the application is already handling a specific transaction and needs the result immediately.

### Scheduled Pending Reconciliation

Enable reconciliation only when the application is ready to let the package update old indeterminate pending transactions:

```env
SISP_TRANSACTION_RECONCILIATION_ENABLED=true
SISP_TRANSACTION_RECONCILE_AFTER_MINUTES=5
SISP_TRANSACTION_RECONCILE_LIMIT=50
```

Then schedule:

```php
use Illuminate\Console\Scheduling\Schedule;

app(Schedule::class)->command('sisp:reconcile-pending')->everyFiveMinutes();
```

The command only selects local transactions where:

- `status = pending`
- `message_type IS NULL`
- `created_at` is older than `SISP_TRANSACTION_RECONCILE_AFTER_MINUTES`

For each selected transaction:

- `result=false`: leaves the transaction as `pending`
- `result=true` and `transactionSuccess=true`: updates it to `completed`
- `result=true` and `transactionSuccess=false`: updates it to `failed`

Use `--force` to run the command manually even when the feature flag is disabled:

```bash
php artisan sisp:reconcile-pending --force --older-than=5 --limit=10
```

## Rate Limiting Configuration

```env
SISP_RATE_LIMITING_ENABLED=true

# Per IP limiting
SISP_RATE_LIMIT_PER_IP=true
SISP_RATE_LIMIT_PER_IP_LIMIT=100
SISP_RATE_LIMIT_PER_IP_WINDOW=3600

# Per merchant limiting
SISP_RATE_LIMIT_PER_MERCHANT=true
SISP_RATE_LIMIT_PER_MERCHANT_LIMIT=500
SISP_RATE_LIMIT_PER_MERCHANT_WINDOW=3600

# Per user limiting
SISP_RATE_LIMIT_PER_USER=true
SISP_RATE_LIMIT_PER_USER_LIMIT=50
SISP_RATE_LIMIT_PER_USER_WINDOW=3600
```

Window is in seconds. Limit is number of requests per window.

## Route Middleware

Customize middleware assigned to package routes in `config/sisp.php`:

```php
'middleware' => [
    'payment' => [Akira\Sisp\Http\Middleware\ProtectPaymentRoute::class],
    'retry' => [],
    'refund' => ['web', 'auth'],
],
```

Use this to add CSRF, authentication, tenancy, or custom authorization checks to browser-originated routes. The payment route keeps duplicate-payment protection by default. The callback route is intentionally not part of this configuration because SISP must be able to post callbacks without browser CSRF middleware.

## Security Configuration

```env
SISP_COLLECT_METADATA=true         # Collect IP, device, geolocation
SISP_DETECT_VPN=false              # Reserved for external VPN detection
SISP_DETECT_PROXY=false            # Reserved for external proxy detection
SISP_CALCULATE_RISK_SCORE=false    # Reserved for external risk scoring
SISP_BLOCK_VPN_PROXY=false         # Reserved for external VPN and proxy blocking
SISP_REQUIRE_WHITELIST=false       # Reserved for external IP whitelist enforcement
```

The package currently enforces blacklist and rate-limit checks. VPN detection,
proxy detection, risk scoring, country blocking, and whitelist enforcement are
not built in. Add custom middleware or application services if your project
requires those controls.

### Amount Limits

```env
SISP_MAX_AMOUNT_PER_DAY=50000      # Max amount per day (in cents)
SISP_MAX_AMOUNT_PER_MONTH=200000   # Max amount per month (in cents)
```

### Geolocation

```env
SISP_GEOLOCATION_PROVIDER=maxmind  # maxmind or ip-api
MAXMIND_KEY=your_maxmind_key
IP_API_KEY=your_ip_api_key
SISP_GEOLOCATION_CACHE_TTL=1440    # Cache in minutes
```

## Database Tables

You can customize table names if needed:

```env
SISP_TABLE_TRANSACTIONS=sisp_transactions
SISP_TABLE_TRANSACTION_ITEMS=sisp_transaction_items
SISP_TABLE_INVOICES=sisp_invoices
SISP_TABLE_REQUEST_METADATA=sisp_request_metadata
SISP_TABLE_RATE_LIMITS=sisp_rate_limits
SISP_TABLE_BLACKLIST=sisp_blacklist
SISP_TABLE_TRANSACTION_LOGS=sisp_transaction_logs
```

The transaction model and retry request validation read `SISP_TABLE_TRANSACTIONS`, so retry links continue to validate correctly when the transaction table is renamed.

## Configuration via Code

You can also configure programmatically in a service provider:

```php
config([
    'sisp.url' => env('SISP_URL'),
    'sisp.posID' => env('SISP_POS_ID'),
    'sisp.posAutCode' => env('SISP_POS_AUT_CODE'),
    'sisp.merchantId' => env('SISP_MERCHANT_ID'),
]);
```

## Multi-Merchant Configuration

### Overview

The package supports both single-tenant and multi-tenant (SaaS) architectures. Credentials can be injected at runtime for each merchant without affecting the global configuration.

### Single Tenant (Default)

The default behavior uses credentials from your `.env` file or `config/sisp.php`:

```php
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\PaymentRequestData;

$request = Sisp::buildRequestPayload(
    PaymentRequestData::from(['amount' => 100.00])
);
```

### Runtime Credentials (Multi-Tenant)

Override credentials at runtime for specific merchants:

```php
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\SispCredentials;
use Akira\Sisp\ValueObjects\PaymentRequestData;

$credentials = SispCredentials::from([
    'pos_id' => 'MERCHANT_POS_123',
    'pos_aut_code' => 'secret_key',
    'currency' => '132',
    'merchant_id' => 'MERCHANT_123',
    'url' => 'https://mc.vinti4net.cv/Client_VbV_v2/biz_vbv_clientdata.jsp',
    'language_messages' => 'EN',
    'fingerprint_version' => '1',
    'is_3d_sec' => '0',
    'sandbox' => false,
    'url_merchant_response' => 'https://yoursite.com/sisp/callback',
]);

$request = Sisp::forCredentials($credentials)
    ->buildRequestPayload(PaymentRequestData::from(['amount' => 100.00]));
```

### Custom Credential Resolvers

For advanced scenarios (database-backed credentials, encrypted storage, etc.):

```php
use Akira\Sisp\Contracts\SispCredentialsResolver;
use Akira\Sisp\ValueObjects\SispCredentials;
use App\Models\Merchant;

class DatabaseCredentialsResolver implements SispCredentialsResolver
{
    public function __construct(private int $merchantId) {}

    public function resolve(): SispCredentials
    {
        $merchant = Merchant::find($this->merchantId);

        return SispCredentials::from([
            'pos_id' => $merchant->sisp_pos_id,
            'pos_aut_code' => decrypt($merchant->sisp_pos_aut_code),
            'currency' => $merchant->currency,
            'merchant_id' => $merchant->sisp_merchant_id,
            'url' => $merchant->sisp_url,
            'language_messages' => $merchant->language ?? 'EN',
            'fingerprint_version' => '1',
            'is_3d_sec' => $merchant->enable_3d_secure ? '1' : '0',
            'sandbox' => $merchant->sisp_sandbox_mode,
            'url_merchant_response' => $merchant->callback_url,
        ]);
    }
}
```

Register in your `AppServiceProvider`:

```php
use Akira\Sisp\Contracts\SispCredentialsResolver;
use App\Services\DatabaseCredentialsResolver;

public function register(): void
{
    $this->app->bind(
        SispCredentialsResolver::class,
        fn() => new DatabaseCredentialsResolver(
            auth()->user()->merchant_id
        )
    );
}
```

With this approach, all Sisp operations automatically use the current user's merchant credentials.

## Environment Variables Reference

```env
# SISP Credentials (Required)
SISP_URL=
SISP_POS_ID=
SISP_POS_AUT_CODE=
SISP_MERCHANT_ID=

# Currency & Language
SISP_CURRENCY=132
SISP_LANGUAGE_MESSAGES=en

# Transaction Settings
SISP_IS_3D_SEC=1
SISP_TRANSACTION_CODE=1

# Sandbox Mode
SISP_SANDBOX=true

# Invoice Configuration
SISP_COMPANY_NAME=
SISP_COMPANY_ADDRESS=
SISP_COMPANY_CODE=
SISP_COMPANY_EMAIL=
SISP_COMPANY_COUNTRY=CV
SISP_COMPANY_PHONE=
SISP_COMPANY_WEBSITE=
SISP_INVOICE_TEMPLATE=modern
SISP_INVOICE_NUMBER_FORMAT=date-based
SISP_INVOICE_NUMBER_PREFIX=INV
SISP_INVOICE_DISK=public

# Rendering Engine
SISP_USE_BLADE=true
SISP_USE_INERTIA=false
SISP_INERTIA_PAYMENT_COMPONENT=sisp/payment-form
SISP_INERTIA_CALLBACK_COMPONENT=sisp/payment-response

# Rate Limiting
SISP_RATE_LIMITING_ENABLED=true
SISP_RATE_LIMIT_PER_IP=true
SISP_RATE_LIMIT_PER_IP_LIMIT=100
SISP_RATE_LIMIT_PER_IP_WINDOW=3600
SISP_RATE_LIMIT_PER_MERCHANT=true
SISP_RATE_LIMIT_PER_MERCHANT_LIMIT=500
SISP_RATE_LIMIT_PER_MERCHANT_WINDOW=3600
SISP_RATE_LIMIT_PER_USER=true
SISP_RATE_LIMIT_PER_USER_LIMIT=50
SISP_RATE_LIMIT_PER_USER_WINDOW=3600

# Security
SISP_COLLECT_METADATA=true
SISP_DETECT_VPN=false
SISP_DETECT_PROXY=false
SISP_CALCULATE_RISK_SCORE=false
SISP_BLOCK_VPN_PROXY=false
SISP_REQUIRE_WHITELIST=false
SISP_MAX_AMOUNT_PER_DAY=
SISP_MAX_AMOUNT_PER_MONTH=

# Geolocation
SISP_GEOLOCATION_PROVIDER=maxmind
MAXMIND_KEY=
IP_API_KEY=
SISP_GEOLOCATION_CACHE_TTL=1440
```

## Next Steps

- Read [Quick Start Guide](./03-quick-start.md)
- Learn [Payment Flow](./04-payment-flow.md)

**Previous:** [Installation](01-installation.md) | **Next:** [Quick Start Guide](03-quick-start.md)
