# Security

Built-in security features to protect your payment system.

Since v2, the blacklist and rate-limit checks run as the first pipes of the payment pipeline (`EnsureIpIsNotBlacklisted` and `EnforceRateLimits`). You can reorder or remove them — or insert your own security pipes — through `config('sisp.pipelines.payment')`. See [Configuration](./02-configuration.md#processing-pipelines-v2).

## Rate Limiting

Prevent abuse by limiting payment requests per IP, merchant, or user.

### Configuration

```env
SISP_RATE_LIMITING_ENABLED=true

# Per IP
SISP_RATE_LIMIT_PER_IP=true
SISP_RATE_LIMIT_PER_IP_LIMIT=100
SISP_RATE_LIMIT_PER_IP_WINDOW=3600

# Per merchant
SISP_RATE_LIMIT_PER_MERCHANT=true
SISP_RATE_LIMIT_PER_MERCHANT_LIMIT=500
SISP_RATE_LIMIT_PER_MERCHANT_WINDOW=3600

# Per user (email)
SISP_RATE_LIMIT_PER_USER=true
SISP_RATE_LIMIT_PER_USER_LIMIT=50
SISP_RATE_LIMIT_PER_USER_WINDOW=3600
```

Window is in seconds. When limit is exceeded, returns HTTP 429.

### Manual Rate Limit Check

```php
use Akira\Sisp\Actions\CheckRateLimitAction;

$action = app(CheckRateLimitAction::class);

try {
    $action->handle(
        limitType: 'ip',
        identifier: request()->ip(),
        context: 'payment',
        limit: 100,
        windowSeconds: 3600
    );
} catch (\Akira\Sisp\Exceptions\RateLimitExceededException $e) {
    // Handle rate limit
}
```

### Rate Limit Status

```php
use Akira\Sisp\Models\RateLimit;

// Check current hits
$rateLimit = RateLimit::where('identifier', request()->ip())
    ->where('limit_type', 'ip')
    ->first();

if ($rateLimit) {
    echo $rateLimit->hits;          // Current hits
    echo $rateLimit->limit;         // Maximum allowed
    echo $rateLimit->reset_at;      // When window resets
    echo $rateLimit->is_blocked;    // Currently blocked
}
```

## Blacklist

Block payments from specific IPs, emails, or identifiers.

### Add to Blacklist

```php
use Akira\Sisp\Actions\CheckBlacklistAction;

$action = app(CheckBlacklistAction::class);

$action->add(
    type: 'ip',
    value: '192.168.1.1',
    severity: 'high',
    reason: 'Suspected fraud',
    notes: 'Multiple failed transactions',
    addedBy: 'admin',
    expiresInMinutes: 1440  // Optional: block for 1 day
);
```

### Check Blacklist

```php
$action = app(CheckBlacklistAction::class);

// Check if value is blacklisted
if ($action->isBlacklisted('ip', '192.168.1.1')) {
    // Value is blacklisted
}

// Throw exception if blacklisted
try {
    $action->handle(type: 'ip', value: '192.168.1.1');
} catch (\Akira\Sisp\Exceptions\BlacklistedIdentifierException $e) {
    // Handle blacklisted identifier
}
```

### Query Blacklist

```php
use Akira\Sisp\Models\Blacklist;
use Akira\Sisp\Enums\BlacklistSeverity;

// All active entries
$blacklist = Blacklist::active()->get();

// By type
$ipBlacklist = Blacklist::active()->byType('ip')->get();
$emailBlacklist = Blacklist::active()->byType('email')->get();

// By severity
$critical = Blacklist::active()->bySeverity('high')->get();

// Expired entries
$expired = Blacklist::expired()->get();
```

### Remove from Blacklist

```php
$action = app(CheckBlacklistAction::class);

$action->remove(type: 'ip', value: '192.168.1.1');
```

## Transaction Replay Protection

The `ProtectPaymentRoute` middleware prevents duplicate payment submissions.

How it works:
- Looks for existing transactions with the same `merchantRef` and `merchantSession`
- Blocks requests when a transaction already exists in `completed`, `failed`, or `pending`
- Redirects to `/` with an error message when blocked

This middleware is applied to `POST /sisp/payment` by default.

## Request Metadata Collection

Automatically collect security and fraud detection data on every payment request.

### Collected Data

```php
$metadata = $transaction->metadata;

echo $metadata->ip_address;         // Client IP address
echo $metadata->user_agent;         // Browser user agent
echo $metadata->device_type;        // mobile/tablet/desktop
echo $metadata->browser;            // Chrome, Firefox, Safari, etc.
echo $metadata->os;                 // Windows, macOS, Linux, iOS, Android
echo $metadata->device_fingerprint; // SHA256 hash of device characteristics
echo $metadata->country_code;       // Geolocation country code
echo $metadata->country_name;       // Geolocation country name
echo $metadata->city;               // Geolocation city
echo $metadata->latitude;           // Geolocation latitude
echo $metadata->longitude;          // Geolocation longitude
echo $metadata->isp;                // Internet service provider
echo $metadata->is_vpn;             // Reserved for external VPN detection
echo $metadata->is_proxy;           // Reserved for external proxy detection
echo $metadata->is_mobile;          // Mobile device (boolean)
echo $metadata->risk_score;         // Reserved risk score, defaults to 0
echo $metadata->risk_reason;        // Reserved risk explanation
```

### Configure Collection

```env
SISP_COLLECT_METADATA=true
SISP_DETECT_VPN=false
SISP_DETECT_PROXY=false
SISP_CALCULATE_RISK_SCORE=false
SISP_BLOCK_VPN_PROXY=false
```

The package does not perform VPN detection, proxy detection, risk scoring, or
whitelist enforcement by itself. These flags are reserved for application-level
integrations. Use blacklist entries, rate limits, or custom middleware to block
requests before creating payment transactions.

## Geolocation

Determine customer location from IP address.

### Configuration

```env
SISP_GEOLOCATION_PROVIDER=maxmind
MAXMIND_KEY=your_maxmind_key
IP_API_KEY=your_ip_api_key
SISP_GEOLOCATION_CACHE_TTL=1440
```

Supported providers:
- `maxmind` - MaxMind GeoIP2 (recommended)
- `ip-api` - IP-API.com

Cache is in minutes (default: 24 hours).

## Query Request Metadata

```php
use Akira\Sisp\Models\RequestMetadata;

// Get metadata for transaction
$metadata = RequestMetadata::where('transaction_id', $transactionId)->first();

// Application-assigned risk values
$risky = RequestMetadata::where('risk_score', '>=', 70)->get();

// Application-assigned VPN and proxy flags
$suspicious = RequestMetadata::where(function ($q) {
    $q->where('is_vpn', true)
        ->orWhere('is_proxy', true);
})->get();

// By country
$byCountry = RequestMetadata::where('country_code', 'PT')->get();

// By device type
$mobile = RequestMetadata::where('device_type', 'mobile')->get();
```

## Signature Verification

All callbacks from SISP are automatically verified using cryptographic signatures. This prevents tampering and ensures authenticity.

The package:
1. Validates the SISP signature on every callback
2. Redirects invalid callback requests to `config('sisp.redirect_url', '/')`
3. Prevents status tampering

No manual configuration needed.

Invalid POST callbacks are rejected by `CallbackController` before transaction lookup or duplicate checks. Signed callbacks are then checked for required merchant reference and merchant session values before processing.

### Callback Fingerprint Formulas

SISP signs a success callback and an error callback with two different formulas, each concatenating a fixed, ordered list of fields before hashing with SHA-512 and base64-encoding the digest. `ValidatePaymentResponseFingerprintAction` picks the formula from `messageType`: exactly `6` uses the error formula, anything else (including a known `SuccessMessageType` case, an unrecognised value, or a null/empty `messageType`) uses the success formula (see the `messageType` table in [Payment Flow](./04-payment-flow.md#91-fingerprint-validation)).

**Success formula** (specification section 2.4.2.1, `PaymentResponseFingerPrintAction`):

1. Encoded `posAutCode`
2. `messageType`
3. `merchantRespCP` (clearing period)
4. `merchantRespTid`
5. `merchantRespMerchantRef`
6. `merchantRespMerchantSession`
7. `merchantRespPurchaseAmount`, in thousandths
8. `merchantRespMessageID`
9. `merchantRespPan`
10. `merchantResp`
11. `merchantRespTimeStamp`
12. `merchantRespReferenceNumber`
13. `merchantRespEntityCode`
14. `merchantRespClientReceipt`
15. `merchantRespAdditionalErrorMessage`
16. `merchantRespReloadCode`

**Error formula** (specification section 2.4.2.2, `PaymentErrorResponseFingerPrintAction`):

1. Encoded `posAutCode`
2. `messageType`
3. `merchantRespMessageID`
4. `merchantRespErrorCode`
5. `merchantRespErrorDetail`
6. `merchantRespErrorDescription`
7. `merchantRespMerchantRef`
8. `merchantRespMerchantSession`
9. `merchantRespAdditionalErrorMessage`
10. `merchantRespTimeStamp`

The error formula puts error detail before error description; a transcription slip commonly swaps the two. `merchantRespErrorCode` is not guaranteed to be numeric: real refused callbacks from production SISP carry letter codes (for example `F`), so neither formula assumes a digit.

Both formulas need the plaintext `posAutCode` (the encoded value comes from `PostAutCode`, which hashes it), a merchant-specific secret this package never logs or exposes. A fingerprint captured from production traffic cannot be reproduced or verified without that secret.

## Who May Act on a Transaction

Three routes change a transaction after the checkout, and each has its own gate:

| Route | Gate | Who holds it |
| --- | --- | --- |
| `GET`/`POST /sisp/retry-payment` | Temporary signed URL (30 minutes), checked in `RetryPaymentRequest::authorize()` | Whoever holds the link the payment result page rendered |
| `GET /sisp/cancel` | Signed URL (`ValidateSignature`), with the expiry you give it | Whoever holds the link your application generated |
| `POST /sisp/refund/{transaction}` | `sisp.middleware.refund` (`web`, `auth` by default) and the `refund` ability on the transaction | An authenticated user your policy allows |

**Retry and cancel links are capabilities.** The signature covers the query string, and both routes read the transaction from the query alone, so a link acts on the transaction it was signed for and no other: a body naming another transaction is ignored. The link itself is the credential. Hand it only to the customer of that payment (the result page, a confirmation email), serve it over HTTPS, and keep it out of logs and analytics. Give cancel links a short expiry:

```php
URL::temporarySignedRoute('sisp.cancel', now()->addMinutes(30), [
    'merchantRef' => $transaction->merchant_ref,
]);
```

The retry route carries no middleware by default (`'retry' => []`), because the signature is checked before the transaction is resolved and a guest who just paid must be able to retry. If only signed-in customers check out, add `auth` to `sisp.middleware.retry` and, in a route middleware of your own, check that the transaction belongs to the user: compare it with an account identifier your application recorded at checkout (the order the `checkout_intent_id` names, or the user id you stored alongside the transaction). `customer_email` is optional in the payment request and nullable on the transaction, so comparing it with the user's email only works when your checkout always collects the account's email.

**Refunds need a policy.** `RefundTransactionRequest` calls `$user->can('refund', $transaction)`. The package registers no ability of that name, and Laravel denies an ability nobody defined, so the route answers `403` for every user until your application decides who may refund. Define it on a policy for `Akira\Sisp\Models\Transaction`, or as a gate:

```php
use Akira\Sisp\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

Gate::define('refund', fn (User $user, Transaction $transaction): bool => $user->isFinanceStaff());
```

Scope it as narrowly as the business allows: a per-merchant application checks that the transaction's `pos_id` belongs to the user's merchant. The route only records the refund (see [Refund Transaction](05-transaction-management.md#refund-transaction)); the money moves in the SISP back office, so the policy decides who may put a refund on the books, not who may move money.

## Data Encryption

Sensitive customer fields are automatically encrypted:

- `customer_email`
- `customer_phone`

These are decrypted automatically when accessed:

```php
$transaction = Transaction::find($id);
echo $transaction->customer_email;  // Automatically decrypted
echo $transaction->customer_phone;  // Automatically decrypted
```

## Next Steps

- [Examples](./08-examples.md) - Code examples and use cases

**Previous:** [Invoice Generation](06-invoice-generation.md) | **Next:** [Examples](08-examples.md)
