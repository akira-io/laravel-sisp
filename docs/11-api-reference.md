# API Reference

Complete reference for models, actions, and events.

## Commands

### sisp:install

Installs and configures the package.

Prompts:

- Publish configuration file? (with optional force overwrite)
- Publish migration files? (with optional force overwrite)
- If Inertia detected: publish Inertia components? (with optional force)
- Else: publish Blade views? (with optional force)
- Run database migrations now?
- Support the project with a GitHub star?

Test toggles (only read during unit tests):

- `sisp.tests.publish_config` / `sisp.tests.force_config`
- `sisp.tests.publish_migrations` / `sisp.tests.force_migrations`
- `sisp.tests.publish_inertia` / `sisp.tests.force_inertia`
- `sisp.tests.publish_blade` / `sisp.tests.force_blade`
- `sisp.tests.run_migrations` – run migrations step
- `sisp.tests.fake_migrate` – short-circuit actual `migrate` call (defaults to true)
- `sisp.tests.give_star` – show star note

Example (Pest):

```php
config()->set('sisp.tests.publish_config', true);
config()->set('sisp.tests.publish_migrations', true);
config()->set('sisp.tests.run_migrations', true);
config()->set('sisp.tests.fake_migrate', true);
Artisan::call('sisp:install', ['--no-interaction' => true]);
```

### sisp:doctor

Diagnoses invoice PDF generation issues (configuration, storage, and invoice status).

Outputs:
- Active invoice disk/path/driver
- Storage read/write checks
- Paid invoices missing PDFs (with sample)

### sisp:regenerate-pdfs

Regenerates PDFs for paid invoices missing `pdf_path`.

Options:

```
--limit=5     # Limit number of invoices processed
```

## Models

### Transaction

The main model representing a payment transaction.

#### Attributes

```php
$transaction->id                  // UUID primary key
$transaction->merchant_ref        // Unique merchant reference
$transaction->merchant_session    // Merchant session ID
$transaction->amount              // Amount in cents (integer)
$transaction->currency            // Currency code (ECV)
$transaction->status              // TransactionStatus enum
$transaction->transaction_code    // SISP transaction type code
$transaction->transaction_id      // SISP's transaction ID
$transaction->message_type        // SISP message type
$transaction->response_code       // SISP response code
$transaction->merchant_response   // SISP response message (encrypted)
$transaction->fingerprint         // Payment signature fingerprint
$transaction->payload             // Full SISP response data (encrypted, array)
$transaction->customer_name       // Customer name
$transaction->customer_email      // Customer email (encrypted)
$transaction->customer_phone      // Customer phone (encrypted)
$transaction->customer_country    // Customer country code
$transaction->customer_city       // Customer city
$transaction->customer_address    // Customer address
$transaction->locale              // Transaction locale (pt, en, etc.)
$transaction->cancelled_at        // When transaction was cancelled
$transaction->refunded_at         // When transaction was refunded
$transaction->created_at          // Created timestamp
$transaction->updated_at          // Updated timestamp
```

#### Accessors

```php
$transaction->formatted_amount    // "1000,00 ECV"
```

#### Relations

```php
$transaction->items()             // HasMany TransactionItem
$transaction->invoice()           // HasOne Invoice
```

#### Scopes

```php
Transaction::where('status', TransactionStatus::completed)
Transaction::where('status', TransactionStatus::failed)
Transaction::where('customer_email', 'test@example.com')
Transaction::whereBetween('created_at', [$start, $end])
Transaction::with('items', 'invoice')
Transaction::paginate(15)
```

### TransactionItem

Line item in a transaction.

#### Attributes

```php
$item->id                         // UUID primary key
$item->transaction_id             // FK to Transaction
$item->product_id                 // Optional product ID
$item->product_name               // Item name (required)
$item->quantity                   // Quantity (integer)
$item->unit_price_cents           // Unit price in cents (integer)
$item->total_price_cents          // Total price in cents (integer)
$item->description                // Item description (optional)
$item->metadata                   // Custom metadata (array)
$item->created_at                 // Created timestamp
$item->updated_at                 // Updated timestamp
```

#### Accessors

```php
$item->unit_price                 // unit_price_cents / 100 (float)
$item->total_price                // total_price_cents / 100 (float)
```

#### Relations

```php
$item->transaction()              // BelongsTo Transaction
```

### Invoice

Generated invoice for a transaction.

#### Attributes

```php
$invoice->id                      // UUID primary key
$invoice->transaction_id          // FK to Transaction
$invoice->invoice_number          // Generated invoice number
$invoice->invoice_date            // Date invoice created
$invoice->due_date                // Payment due date
$invoice->status                  // InvoiceStatus enum
$invoice->customer_name           // Customer name (from transaction)
$invoice->customer_email          // Customer email (from transaction)
$invoice->customer_city           // Customer city (from transaction)
$invoice->customer_address        // Customer address (from transaction)
$invoice->customer_country        // Customer country (from transaction)
$invoice->notes                   // Optional invoice notes
$invoice->pdf_path                // Path to generated PDF
$invoice->metadata                // Custom metadata (array)
$invoice->created_at              // Created timestamp
$invoice->updated_at              // Updated timestamp
```

#### Relations

```php
$invoice->transaction()           // BelongsTo Transaction
$invoice->items()                 // HasMany (via transaction)
```

### RequestMetadata

Security and fraud detection data for a payment request.

#### Attributes

```php
$metadata->id                     // UUID primary key
$metadata->transaction_id         // FK to Transaction (nullable)
$metadata->ip_address             // Client IP address
$metadata->user_agent             // Browser user agent
$metadata->referer                // HTTP referer header
$metadata->country_code           // Geolocation country code
$metadata->country_name           // Geolocation country name
$metadata->region                 // Geolocation region
$metadata->city                   // Geolocation city
$metadata->latitude               // Geolocation latitude (float)
$metadata->longitude              // Geolocation longitude (float)
$metadata->isp                    // Internet service provider
$metadata->device_type            // mobile/tablet/desktop
$metadata->browser                // Chrome/Firefox/Safari/IE/Edge
$metadata->os                     // Windows/macOS/Linux/iOS/Android
$metadata->device_fingerprint     // SHA256 hash of device
$metadata->response_time_ms       // API response time in milliseconds
$metadata->api_version            // API version used
$metadata->is_vpn                 // VPN detected (boolean)
$metadata->is_proxy               // Proxy detected (boolean)
$metadata->is_mobile              // Mobile device (boolean)
$metadata->risk_score             // Fraud risk score 0-100
$metadata->risk_reason            // Why risk was assigned
$metadata->custom_metadata        // Custom metadata (array)
$metadata->created_at             // Created timestamp
$metadata->updated_at             // Updated timestamp
```

#### Relations

```php
$metadata->transaction()          // BelongsTo Transaction
```

#### Scopes

```php
RequestMetadata::where('risk_score', '>=', 70)
RequestMetadata::where('is_vpn', true)
RequestMetadata::where('country_code', 'PT')
RequestMetadata::where('device_type', 'mobile')
```

### RateLimit

Rate limiting tracking.

#### Attributes

```php
$rateLimit->id                    // Primary key
$rateLimit->identifier            // IP, email, or user ID
$rateLimit->limit_type            // ip/merchant/user
$rateLimit->context               // Context key (optional)
$rateLimit->hits                  // Current hit count
$rateLimit->limit                 // Maximum allowed hits
$rateLimit->window_seconds        // Time window in seconds
$rateLimit->is_blocked            // Currently blocked (boolean)
$rateLimit->reset_at              // When window resets
$rateLimit->blocked_until         // When block expires
$rateLimit->created_at            // Created timestamp
$rateLimit->updated_at            // Updated timestamp
```

#### Methods

```php
$rateLimit->isLimitExceeded()     // Boolean
$rateLimit->recordHit()           // Increment hits
$rateLimit->reset()               // Reset counter
$rateLimit->block(?int $seconds)  // Block this identifier
```

#### Scopes

```php
RateLimit::active()               // Not expired
RateLimit::blocked()              // Currently blocked
RateLimit::where('limit_type', 'ip')
```

### Blacklist

Blocked identifiers.

#### Attributes

```php
$entry->id                        // Primary key
$entry->type                      // ip/email/phone/etc
$entry->value                     // The blocked value
$entry->severity                  // low/medium/high/critical
$entry->reason                    // Why it's blocked
$entry->notes                     // Additional notes
$entry->added_by                  // Who added it
$entry->expires_at                // When block expires (nullable)
$entry->created_at                // Created timestamp
$entry->updated_at                // Updated timestamp
```

#### Methods

```php
$entry->isActive()                // Boolean
$entry->isExpired()               // Boolean
```

#### Scopes

```php
Blacklist::active()               // Not expired
Blacklist::expired()              // Expired entries
Blacklist::byType('ip')
Blacklist::bySeverity('high')
```

## Value Objects

### PaymentRequestData

Container for payment request fields and 3D Secure customer data.

```php
use Akira\Sisp\ValueObjects\PaymentRequestData;

$data = new PaymentRequestData(
    amount: 100.50,
    merchantRef: 'REF123',
    merchantSession: 'SESSION456',
    timeStamp: '20240101000000',
    currency: '132',
    transactionCode: '1',
    customerEmail: 'customer@example.com',
    customerCountry: 'PT',
    customerCity: 'Lisbon',
    customerAddress: 'Rua Augusta',
    customerPostalCode: '1100-048',
    customerPhone: '123456789',
);

$data->hasThreeDSecureData();          // bool
$data->getMissingThreeDSecureFields(); // array
```

`PaymentRequestData::from()` accepts array keys in snake_case for customer fields
(`customer_email`, `customer_country`, `customer_city`, `customer_address`,
`customer_postal_code`, `customer_phone`).

### ThreeDSecureData

Builds the 3D Secure purchaseRequest payload.

```php
use Akira\Sisp\ValueObjects\ThreeDSecureData;

$threeDS = ThreeDSecureData::fromCustomerData(
    email: 'customer@example.com',
    country: 'PT',
    city: 'Lisbon',
    address: 'Rua Augusta',
    postalCode: '1100-048',
    phone: '123456789',
);
```

## Events

### PaymentCompleted

Fired when payment succeeds.

```php
PaymentCompleted::class {
    public Transaction $transaction
    public array $payload
}
```

### PaymentFailed

Fired when payment fails.

```php
PaymentFailed::class {
    public Transaction $transaction
    public array $payload
}
```

### PaymentPending

Fired when payment remains pending.

```php
PaymentPending::class {
    public Transaction $transaction
    public array $payload
}
```

### TransactionCancelled

Fired when transaction is cancelled.

```php
TransactionCancelled::class {
    public Transaction $transaction
    public string $reason
}
```

### TransactionRefunded

Fired when transaction is refunded.

```php
TransactionRefunded::class {
    public Transaction $transaction
    public float $refundAmount
    public string $reason
}
```

## Enums

### TransactionStatus

```php
TransactionStatus::pending        // Awaiting SISP response
TransactionStatus::completed      // Payment successful
TransactionStatus::failed         // Payment rejected
TransactionStatus::cancelled      // Transaction cancelled
TransactionStatus::refunded       // Fully refunded
TransactionStatus::partially_refunded // Partially refunded
```

### InvoiceStatus

```php
InvoiceStatus::pending            // Not yet issued
InvoiceStatus::issued             // Sent to customer
InvoiceStatus::paid               // Payment confirmed
InvoiceStatus::overdue            // Past due date
InvoiceStatus::cancelled          // Cancelled
```

## Actions

Common actions for payment processing.

### CheckRateLimitAction

Check if rate limit is exceeded.

```php
app(CheckRateLimitAction::class)->handle(
    string $limitType = 'ip',           // ip/merchant/user
    ?string $identifier = null,         // IP/email/etc
    ?string $context = null,            // Context key
    ?int $limit = null,                 // Max requests
    ?int $windowSeconds = null          // Time window
): void

// Throws RateLimitExceededException if limit exceeded
```

### CheckBlacklistAction

Check and manage blacklist.

```php
$action = app(CheckBlacklistAction::class);

// Check if blacklisted (throws exception)
$action->handle(string $type, ?string $value): void

// Check if value is blacklisted (returns boolean)
$action->isBlacklisted(string $type, string $value): bool

// Add to blacklist
$action->add(
    string $type,
    string $value,
    string $severity = 'medium',
    ?string $reason = null,
    ?string $notes = null,
    ?string $addedBy = null,
    ?int $expiresInMinutes = null
): Blacklist

// Remove from blacklist
$action->remove(string $type, string $value): bool
```

### BuildRequestPayloadAction

Build the full SISP request payload, including 3D Secure purchaseRequest when enabled.

```php
app(BuildRequestPayloadAction::class)->handle(
    PaymentRequestData $data
): PaymentRequest

// Throws MissingThreeDSecureDataException when required 3DS fields are missing
```

### BuildPurchaseRequestAction

Build the 3D Secure `purchaseRequest` payload for SISP.

```php
app(BuildPurchaseRequestAction::class)->handle(
    ThreeDSecureData $data
): string // base64-encoded JSON
```

### CancelTransactionAction

Cancel a pending or failed transaction.

```php
app(CancelTransactionAction::class)->handle(
    Transaction $transaction,
    string $reason = 'user_cancelled'
): Transaction

// Throws LogicException if cannot cancel
```

### RefundTransactionAction

Refund a completed transaction.

```php
app(RefundTransactionAction::class)->handle(
    Transaction $transaction,
    float $refundAmount,
    string $reason = 'user_refund'
): Transaction

// Throws LogicException if cannot refund
```

### GenerateInvoiceAction

Generate invoice for transaction.

```php
app(GenerateInvoiceAction::class)->handle(
    Transaction $transaction
): Invoice
```

### GenerateInvoicePdfAction

Generate PDF for invoice.

```php
app(GenerateInvoicePdfAction::class)->handle(
    Invoice $invoice
): string // Returns pdf_path
```

### GetPaymentErrorResponseAction

Transform SISP error codes into structured, user-friendly error responses.

```php
$action = app(GetPaymentErrorResponseAction::class);

// Transform error message type to structured response
$errorResponse = $action->handle(
    ErrorMessageType $errorType
): PaymentErrorResponse

// $errorResponse contains:
[
    'code' => 'card_declined',           // SISP error code identifier
    'label' => 'Card Declined',          // Human-readable label (translated)
    'category' => 'card',                 // Error category
    'categoryLabel' => 'Card Issue',      // Category label (translated)
    'action' => 'use-different-card',    // Suggested action for user
    'actionLabel' => 'Try Another Card', // Action label (translated)
]
```

### RetryPaymentAction

Extract transaction data and build fresh payment request for retry.

```php
app(RetryPaymentAction::class)->handle(
    Transaction $transaction
): PaymentRequest

// Rebuilds payment form with original transaction data
```

### ValidatePaymentResponseFingerprintAction

Validate SHA512 fingerprint from SISP callback.

```php
$isValid = app(ValidatePaymentResponseFingerprintAction::class)->handle(
    CallbackPayload $payload
): bool

// Returns true if fingerprint is valid (data integrity confirmed)
// Returns false if tampering detected
```

### PreventDuplicateCallbackAction

Prevent double-processing of callback requests.

Registered as `PreventDuplicateCallback` middleware on callback routes.

```php
// Automatically redirects already-processed callbacks
// to payment response page
```

### RenderPaymentResponseAction

Render payment response in Blade or Inertia format.

```php
app(RenderPaymentResponseAction::class)->renderBlade(
    Transaction $transaction,
    ?string $renderEngine = 'blade'
): View|Response

app(RenderPaymentResponseAction::class)->renderInertia(
    Transaction $transaction,
    ?string $renderEngine = 'inertia'
): Response

// Includes:
// - Transaction data
// - Structured error response (if failed)
// - allowRetry configuration flag
// - Translations for UI text
```

### PaymentResponseFingerPrintAction

Generate SHA512 fingerprint for callback validation.

```php
$fingerprint = app(PaymentResponseFingerPrintAction::class)->handle(
    CallbackPayload $payload
): string

// Generates base64-encoded SHA512 hash of:
// - posAutCode + messageType + amount + reference + timestamp + ...
```

### BuildSandboxPayloadAction

Generate test callback payload with valid fingerprint for sandbox testing.

```php
$callbackPayload = app(BuildSandboxPayloadAction::class)->handle(
    PaymentRequestData $data,
    string $status = 'success'  // 'success', 'failed', or other
): CallbackPayload

// Includes auto-generated valid fingerprint
// Useful for testing payment response handling
```

## Middleware

### ProtectPaymentRoute

Protects the payment route from duplicate submissions.

```php
// Applied to POST /sisp/payment
// Blocks duplicate merchantRef + merchantSession combinations
```

### PreventDuplicateCallback

Prevents double-processing of callback requests.

```php
// Applied to GET|POST /sisp/callback
// Redirects already-processed callbacks to response page
```

## Enums

### ErrorMessageType

SISP payment error codes with categories and suggested actions.

```php
// Card Issues (user's card cannot be used)
ErrorMessageType::cardDeclined           // "6"
ErrorMessageType::cardExpired            // Generic card error
ErrorMessageType::cardBlocked            // Card is blocked
ErrorMessageType::invalidCardNumber      // Invalid card format

// Insufficient Funds
ErrorMessageType::insufficientFunds      // Not enough balance
ErrorMessageType::transactionLimitExceeded // Amount exceeds limit

// Security Issues
ErrorMessageType::fraudDetected          // Transaction flagged
ErrorMessageType::cvvFailed              // Invalid CVV/CVC
ErrorMessageType::suspiciousActivity     // Unusual pattern detected

// Validation Issues
ErrorMessageType::invalidAmount          // Amount invalid
ErrorMessageType::invalidCurrency        // Currency mismatch
ErrorMessageType::invalidMerchant        // Merchant not configured
ErrorMessageType::missingField           // Required field missing

// System/Gateway Issues
ErrorMessageType::gatewayTimeout         // SISP timeout
ErrorMessageType::processingError        // Generic processing error
ErrorMessageType::serviceUnavailable     // SISP down
ErrorMessageType::bankRejected           // Bank rejected transaction

// Issuer Issues
ErrorMessageType::issuerDecline          // Card issuer declined
ErrorMessageType::issuerError            // Issuer system error

// Unknown/Other
ErrorMessageType::unknownError           // Unclassified error
ErrorMessageType::other                  // Catch-all

// Translated labels available for: EN (English), PT (Portuguese)
```

### SuccessMessageType

SISP success response message types.

```php
SuccessMessageType::purchase             // "P" - Purchase transaction
SuccessMessageType::other                // "O" - Other transaction type
```

## Exceptions

### RateLimitExceededException

Thrown when rate limit is exceeded.

```php
catch (RateLimitExceededException $e) {
    // HTTP 429
}
```

### BlacklistedIdentifierException

Thrown when identifier is blacklisted.

```php
catch (BlacklistedIdentifierException $e) {
    // HTTP 403
}
```

### MissingThreeDSecureDataException

Thrown when 3D Secure is enabled but required customer data is missing.

```php
catch (MissingThreeDSecureDataException $e) {
    // Provide the missing fields and retry
}
```

### InvalidSignatureException

Thrown when a callback fingerprint/signature is invalid.

```php
catch (InvalidSignatureException $e) {
    // Reject callback and log attempt
}
```

## Utilities

### Countries

```php
use Akira\Sisp\Support\Countries;

$all = Countries::all();                 // array keyed by alpha2 (lowercase)
Countries::getNumericCode('PT');         // "620"
Countries::getFlag('PT');                // "https://flagcdn.com/pt.svg"
Countries::getName('PT');                // "Portugal"
```

### CountryCodeMapper

```php
use Akira\Sisp\Support\CountryCodeMapper;

CountryCodeMapper::toNumeric('PT');      // "620"
```

### Sisp Facade Helpers

```php
use Akira\Sisp\Facades\Sisp;

Sisp::countries();                       // Countries::all()
Sisp::getCountryNumericCode('PT');       // "620"
Sisp::getCountryFlag('PT');              // "https://flagcdn.com/pt.svg"
Sisp::getCountryName('PT');              // "Portugal"
```

### Sisp Facade Methods

All `Sisp` service methods are available statically via the facade (with IDE/static analysis support).

```php
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Akira\Sisp\ValueObjects\TransactionData;

Sisp::getTransactions();                               // Builder
Sisp::buildRequestPayload(PaymentRequestData::from([])); // PaymentRequest
Sisp::validateCallback(CallbackPayload::from([]));     // bool
Sisp::handlePaymentCallback(CallbackPayload::from([])); // Transaction
Sisp::generateSandboxPayload(PaymentRequestData::from([]), 'success'); // CallbackPayload
Sisp::storeTransaction(TransactionData::from([]));     // Transaction

Sisp::getMerchantReference();                          // string
Sisp::getMerchantSession();                            // string
Sisp::getTimeStamp();                                  // string
Sisp::getCurrency();                                   // string
Sisp::getPosId();                                      // string
Sisp::getPosAutCode();                                 // string
Sisp::getIs3Dsec();                                    // string
Sisp::getUrlMerchantResponse();                        // string
Sisp::getLanguageMessages();                           // string
Sisp::getFingerprintVersion();                         // string
Sisp::getDefaultTransactionCode();                     // string
Sisp::getUri();                                        // string
```

## Multi-Merchant API

### SispCredentials Value Object

Immutable representation of merchant credentials.

```php
use Akira\Sisp\ValueObjects\SispCredentials;

// From array with snake_case keys
$credentials = SispCredentials::from([
    'pos_id' => 'POS_123',
    'pos_aut_code' => 'secret_key',
    'currency' => '132',
    'merchant_id' => 'MERCHANT_123',
    'url' => 'https://mc.vinti4net.cv/Client_VbV_v2/biz_vbv_clientdata.jsp',
    'language_messages' => 'EN',          // optional, default: 'EN'
    'fingerprint_version' => '1',          // optional, default: '1'
    'is_3d_sec' => '0',                    // optional, default: '0'
    'sandbox' => false,                    // optional, default: false
    'url_merchant_response' => null,       // optional, default: null
]);

// From LoadConfig instance (backward compatibility)
$config = app(\Akira\Sisp\Configuration\LoadConfig::class);
$credentials = SispCredentials::fromConfig($config);
```

#### Properties

All properties are readonly:

```php
$credentials->posId                  // string
$credentials->posAutCode             // string
$credentials->currency               // string
$credentials->merchantId             // string
$credentials->url                    // string
$credentials->languageMessages       // string
$credentials->fingerprintVersion     // string
$credentials->is3DSec                // string ('0' or '1')
$credentials->sandbox                // bool
$credentials->urlMerchantResponse    // ?string
```

### Sisp::forCredentials()

Create a scoped Sisp instance with custom credentials.

```php
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\SispCredentials;

$credentials = SispCredentials::from([...]);

$scoped = Sisp::forCredentials($credentials);
```

Returns a `ScopedSisp` instance with all the same methods as the main `Sisp` service.

### ScopedSisp

Scoped service instance that uses specific credentials without mutating global state.

#### Methods

All methods from `Sisp` are available:

```php
$scoped->buildRequestPayload($data)         // PaymentRequest
$scoped->validateCallback($payload)          // bool
$scoped->handlePaymentCallback($payload)     // Transaction
$scoped->generateSandboxPayload($data)       // CallbackPayload
$scoped->storeTransaction($data)             // Transaction
$scoped->getMerchantReference()              // string
$scoped->getMerchantSession()                // string
$scoped->getTimeStamp()                      // string
$scoped->getCurrency()                       // string
$scoped->getPosId()                          // string
$scoped->getPosAutCode()                     // string
$scoped->getIs3Dsec()                        // string
$scoped->getUrlMerchantResponse()            // string
$scoped->getLanguageMessages()               // string
$scoped->getFingerprintVersion()             // string
$scoped->getDefaultTransactionCode()         // string
$scoped->getUri()                            // string
```

### SispCredentialsResolver Interface

Implement custom credential resolution strategies.

```php
use Akira\Sisp\Contracts\SispCredentialsResolver;
use Akira\Sisp\ValueObjects\SispCredentials;

class CustomResolver implements SispCredentialsResolver
{
    public function resolve(): SispCredentials
    {
        // Your custom logic here
        return SispCredentials::from([...]);
    }
}
```

#### Built-in Implementations

**EnvSispCredentialsResolver** (default):
- Reads credentials from config/env via `LoadConfig`
- Maintains 100% backward compatibility

**ScopedSispCredentialsResolver**:
- Used internally by `ScopedSisp`
- Returns pre-configured credentials

#### Custom Resolver Registration

Register in your service provider:

```php
use Akira\Sisp\Contracts\SispCredentialsResolver;
use App\Services\DatabaseCredentialsResolver;

public function register(): void
{
    $this->app->bind(
        SispCredentialsResolver::class,
        DatabaseCredentialsResolver::class
    );
}
```

Once registered, all Sisp operations automatically use the custom resolver:

```php
// No explicit credentials needed - resolver handles it
$request = Sisp::buildRequestPayload($data);
```

## Routes

All routes are registered automatically with `/sisp` prefix.

```
POST   /sisp/payment           -> PaymentController
GET    /sisp/callback          -> CallbackController
POST   /sisp/callback          -> CallbackController
POST   /sisp/retry-payment     -> RetryPaymentController
GET    /sisp/cancel            -> CancelTransactionController
POST   /sisp/refund/{transaction} -> RefundTransactionController
GET    /sisp/sandbox           -> SandboxController
POST   /sisp/sandbox           -> SandboxController
GET    /sisp/countries         -> CountriesController
```

Refund route middleware is configurable via `config('sisp.middleware.refund')`.

Route names:

```php
route('sisp.payment')
route('sisp.callback')
route('sisp.retry-payment')
route('sisp.cancel')
route('sisp.refund')
route('sisp.sandbox')
route('sisp.countries')
```

## Configuration

All configuration keys under `sisp.*`:

```php
config('sisp.url')
config('sisp.pos_id')
config('sisp.pos_aut_code')
config('sisp.merchant_id')
config('sisp.currency')
config('sisp.language_messages')
config('sisp.sandbox')
config('sisp.allow_retry')             // Enable/disable retry button (default: true)
config('sisp.rate_limiting.enabled')
config('sisp.rate_limiting.per_ip.limit')
config('sisp.rate_limiting.per_merchant.limit')
config('sisp.rate_limiting.per_user.limit')
config('sisp.invoice.company_name')
config('sisp.invoice.company_address')
config('sisp.invoice.company_code')
config('sisp.invoice.company_email')
config('sisp.invoice.company_country')
config('sisp.invoice.company_phone')
config('sisp.invoice.company_website')
config('sisp.invoice.template')
config('sisp.tables.transactions')
config('sisp.tables.transaction_items')
config('sisp.tables.invoices')
config('sisp.tables.request_metadata')
config('sisp.tables.rate_limits')
config('sisp.tables.blacklist')
```

## Next Steps

- Review [Examples](./08-examples.md) for code samples
- Check [Troubleshooting](./09-troubleshooting.md) for solutions

**Previous:** [FAQ](10-faq.md)
