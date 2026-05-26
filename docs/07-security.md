# Security

Built-in security features to protect your payment system.

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
2. Rejects any callbacks with invalid signatures
3. Prevents status tampering

No manual configuration needed.

Invalid signatures raise `InvalidSignatureException` inside the callback handler.

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
