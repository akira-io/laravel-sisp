# Security & Fraud Detection

Comprehensive guide to security features, rate limiting, and fraud detection.

## Overview

Laravel SISP includes enterprise-grade security features:
- Request metadata collection (IP, device, geolocation)
- Rate limiting (per IP, per merchant, per user)
- Blacklist/whitelist management
- Risk scoring
- Device fingerprinting
- VPN/Proxy detection
- Geolocation anomaly detection

## Database Tables

### sisp_request_metadata

Stores comprehensive request information for every payment attempt.

Fields:
- `ip_address` - Client IP address
- `user_agent` - Browser user agent
- `referer` - HTTP referer
- `country_code` - ISO country code (e.g., 'CV' for Cape Verde)
- `country_name` - Full country name
- `region` - State/region
- `city` - City
- `latitude`, `longitude` - Geolocation coordinates
- `isp` - Internet Service Provider
- `device_type` - 'mobile', 'tablet', 'desktop'
- `browser` - 'Chrome', 'Firefox', 'Safari', etc.
- `os` - Operating system
- `device_fingerprint` - SHA256 hash of device characteristics
- `response_time_ms` - Request processing time
- `api_version` - API version used
- `is_vpn` - Boolean VPN detection
- `is_proxy` - Boolean proxy detection
- `is_mobile` - Boolean mobile detection
- `risk_score` - 0-100 risk assessment
- `risk_reason` - Why this request was flagged
- `custom_metadata` - JSON field for custom data

### sisp_rate_limits

Tracks and enforces rate limits.

Fields:
- `identifier` - IP, user ID, or merchant ID
- `limit_type` - 'ip', 'user', 'merchant', 'product'
- `context` - Additional context (product SKU, merchant ID)
- `hits` - Number of hits in current window
- `limit` - Maximum hits allowed
- `window_seconds` - Time window (3600 = 1 hour)
- `reset_at` - When counter resets
- `is_blocked` - Boolean blocking status
- `blocked_until` - When block expires

### sisp_blacklist

Manages blocked identifiers.

Fields:
- `type` - 'ip', 'email', 'phone', 'card_hash', 'device_fingerprint'
- `value` - The identifier to block
- `reason` - Why it's blocked
- `severity` - 'low', 'medium', 'high', 'critical'
- `notes` - Additional notes
- `added_by` - Admin username
- `expires_at` - Expiration (null = permanent)

## Configuration

### Enable/Disable Security Features

```env
SISP_COLLECT_METADATA=true
SISP_DETECT_VPN=true
SISP_DETECT_PROXY=true
SISP_CALCULATE_RISK_SCORE=true
```

### Rate Limiting Configuration

```env
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
```

### Security Policies

```env
SISP_BLOCK_NEW_COUNTRY_PAYMENTS=false
SISP_BLOCK_VPN_PROXY=true
SISP_REQUIRE_WHITELIST=false

SISP_MAX_AMOUNT_PER_DAY=null
SISP_MAX_AMOUNT_PER_MONTH=null
```

### Geolocation Provider

```env
SISP_GEOLOCATION_PROVIDER=maxmind
MAXMIND_KEY=your_maxmind_key
IP_API_KEY=your_ip_api_key
SISP_GEOLOCATION_CACHE_TTL=1440
```

## Usage Examples

### Store Request Metadata

```php
use Akira\Sisp\Actions\StoreRequestMetadataAction;
use Illuminate\Http\Request;

$storeMetadata = app(StoreRequestMetadataAction::class);

$metadata = $storeMetadata->handle($request, $transaction);

// Access metadata
echo $metadata->country_code; // 'CV'
echo $metadata->device_type; // 'mobile'
echo $metadata->is_vpn; // false
echo $metadata->risk_score; // 25
```

### Check Rate Limits

```php
use Akira\Sisp\Actions\CheckRateLimitAction;
use Akira\Sisp\Exceptions\RateLimitExceededException;

$checkRateLimit = app(CheckRateLimitAction::class);

try {
    $checkRateLimit->handle(
        identifier: $request->ip(),
        limitType: 'ip',
        limit: 100,
        windowSeconds: 3600
    );
} catch (RateLimitExceededException $e) {
    return response()->json(['error' => $e->getMessage()], 429);
}
```

### Manage Blacklist

```php
use Akira\Sisp\Actions\CheckBlacklistAction;

$checkBlacklist = app(CheckBlacklistAction::class);

// Check if blacklisted
$checkBlacklist->handle('ip', '192.168.1.1');

// Or check silently
if ($checkBlacklist->isBlacklisted('ip', '192.168.1.1')) {
    // Handle blacklisted request
}

// Add to blacklist
$checkBlacklist->add(
    type: 'ip',
    value: '192.168.1.1',
    severity: 'high',
    reason: 'Multiple failed payments',
    notes: 'Suspicious activity detected',
    addedBy: 'admin@example.com',
    expiresInMinutes: 1440 // 24 hours
);

// Remove from blacklist
$checkBlacklist->remove('ip', '192.168.1.1');
```

### Register Middleware

In `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // ...
    'sisp.rate_limit' => \Akira\Sisp\Http\Middleware\EnforceSispRateLimits::class,
];
```

Use on routes:

```php
Route::post('/payment', [PaymentController::class, 'store'])
    ->middleware('sisp.rate_limit');
```

## Metadata Collected

### Request Information
- IP address (real IP, not proxy)
- HTTP headers (User-Agent, Referer, Accept-Language)
- Request method and path

### Device Fingerprinting
- Unique hash combining:
  - IP address
  - User-Agent
  - Accept-Language header
  - Accept-Encoding header
  - Custom device characteristics

### Geolocation Data
- Country code and name
- State/region
- City
- Latitude/longitude coordinates
- ISP

### Device Details
- Device type (mobile, tablet, desktop)
- Browser (Chrome, Firefox, Safari, IE, Edge)
- Operating system (Windows, macOS, Linux, Android, iOS)
- Mobile detection
- VPN/Proxy detection

### Performance
- Response time in milliseconds
- API version used

## Risk Scoring

Risk scores range from 0-100:

- 0-20: Low risk
- 21-40: Medium risk
- 41-60: High risk
- 61-100: Critical risk

Factors that increase risk:
- New geolocation (not seen before)
- VPN/Proxy usage (configurable)
- Unusual device (new fingerprint)
- High velocity (many requests in short time)
- Failed payment attempts
- Amount anomalies

## Rate Limiting Scenarios

### Scenario 1: Per IP Limit

```
Rate Limit: 100 requests per hour per IP

User makes 101st request from IP 192.168.1.1
-> RateLimitExceededException thrown
-> Response: 429 Too Many Requests
-> IP is blocked for remaining window
```

### Scenario 2: Per User Limit

```
Rate Limit: 50 requests per hour per user

User makes 51st request
-> Check against merchant user limit
-> Block user from making more payments
-> Send notification to user
```

### Scenario 3: Per Merchant Limit

```
Rate Limit: 500 requests per hour per merchant

Merchant's total API requests exceed 500
-> Block all users under this merchant
-> Alert merchant about rate limit
-> Provide upgrade options
```

## Blacklist Examples

### Block Suspicious IP

```php
$checkBlacklist->add(
    type: 'ip',
    value: '203.0.113.45',
    severity: 'critical',
    reason: 'Brute force attack detected',
    addedBy: 'security-system',
    expiresInMinutes: 4320 // 3 days
);
```

### Block Compromised Card

```php
$checkBlacklist->add(
    type: 'card_hash',
    value: 'sha256_hash_of_card_number',
    severity: 'high',
    reason: 'Card reported stolen',
    addedBy: 'fraud-team',
    expiresInMinutes: null // Permanent
);
```

### Block Device Fingerprint

```php
$checkBlacklist->add(
    type: 'device_fingerprint',
    value: 'abc123def456...',
    severity: 'medium',
    reason: 'Multiple chargeback claims',
    addedBy: 'compliance-team',
    expiresInMinutes: 10080 // 1 week
);
```

### Whitelist Exception (Negative Entry)

```php
// Store whitelist as blacklist with negative entries
Blacklist::create([
    'type' => 'ip_whitelist',
    'value' => '192.168.1.1',
    'reason' => 'Trusted partner IP',
    'severity' => 'low',
]);

// Check when processing
if (!$checkBlacklist->isBlacklisted('ip_whitelist', $request->ip())) {
    // Process normally, skip rate limit
}
```

## Fraud Detection Patterns

### High Velocity Payments

```php
$recentPayments = Transaction::where('user_id', $userId)
    ->where('created_at', '>', now()->subMinutes(5))
    ->count();

if ($recentPayments > 5) {
    $metadata->update(['risk_reason' => 'High velocity payment']);
}
```

### Geographic Anomaly

```php
$lastPaymentCountry = RequestMetadata::where('transaction_id', '<>', $transaction->id)
    ->where('user_id', $userId)
    ->latest()
    ->first()?->country_code;

if ($lastPaymentCountry !== $metadata->country_code) {
    $metadata->update(['risk_reason' => 'Geographic anomaly']);
}
```

### Device Change

```php
$lastDevice = RequestMetadata::where('user_id', $userId)
    ->where('device_fingerprint', '<>', $metadata->device_fingerprint)
    ->latest()
    ->first();

if ($lastDevice !== null) {
    $metadata->update(['risk_reason' => 'New device detected']);
}
```

### Amount Anomaly

```php
$averageAmount = Transaction::where('user_id', $userId)
    ->avg('amount');

if ($transaction->amount > $averageAmount * 3) {
    $metadata->update(['risk_reason' => 'Amount exceeds user average']);
}
```

## Querying Metadata

```php
// Get high-risk requests
$risky = RequestMetadata::where('risk_score', '>', 60)->get();

// Get VPN users
$vpnUsers = RequestMetadata::where('is_vpn', true)->get();

// Get requests from specific country
$cvRequests = RequestMetadata::where('country_code', 'CV')->get();

// Get requests in specific time window
$lastHour = RequestMetadata::where('created_at', '>', now()->subHour())->get();

// Get failed rate limits
$failed = RateLimit::where('is_blocked', true)->get();

// Get active blacklist
$active = Blacklist::active()->get();
$critical = Blacklist::active()->bySeverity('critical')->get();
```

## Security Best Practices

1. Enable metadata collection in production
2. Monitor risk scores regularly
3. Review blacklist entries weekly
4. Set appropriate rate limits based on usage patterns
5. Use VPN/proxy detection to block fraudulent access
6. Implement geolocation anomaly detection
7. Monitor for high-velocity payments
8. Set up alerts for critical severity blacklist additions
9. Regularly audit and clean up expired blacklist entries
10. Keep geolocation data updated (use MaxMind GeoIP2)

## Performance Considerations

Metadata collection adds minimal overhead:
- ~5-10ms per request for geolocation lookup
- ~2-3ms for device fingerprinting
- ~1-2ms for rate limit checks
- Cached geolocation data (TTL configurable)

Rate limiting uses in-memory caching for performance:
- First hit: Database write + cache
- Subsequent hits: Cache only (very fast)
- Automatic cleanup of expired entries

## Alerting & Monitoring

### Events to Monitor

- RateLimitExceeded (429 responses)
- BlacklistedIdentifierException (403 responses)
- High-risk transactions (risk_score > 60)
- New VPN/Proxy detection
- Geographic anomalies
- Device fingerprint changes
- Amount anomalies

### Suggested Actions

```php
// Listen to rate limit events
Event::listen(function (RateLimitExceeded $event) {
    // Alert security team
    // Log to security monitoring system
    // Potentially block user temporarily
});

// Listen to blacklist events
Event::listen(function (BlacklistedIdentifierException $event) {
    // Log to fraud system
    // Review and potentially escalate
    // Contact user if account compromised
});
```

## Compliance

This security system helps with:
- PCI-DSS compliance (fraud detection)
- GDPR compliance (collecting IP/location with consent)
- KYC/AML requirements (transaction tracking)
- Chargeback prevention
- Fraud reduction

Ensure proper consent and privacy policy updates when enabling data collection.

## Troubleshooting

### Rate Limit Always Triggered

Check if reset_at is in the past:
```php
$limit = RateLimit::find(1);
echo $limit->reset_at; // Should be in future
```

### VPN Detection Not Working

Ensure MaxMind or IP API credentials are configured:
```env
MAXMIND_KEY=your_key
```

### Metadata Not Collected

Check if middleware is registered and feature enabled:
```env
SISP_COLLECT_METADATA=true
```

## See Also

- Configuration guide: `docs/configuration.md`
- Rate Limiting Guide: `docs/guides/rate-limiting.md` (detailed rate limit mechanics)
- Architecture: `docs/architecture.md`
- Payment flow: `docs/payment-flow.md`
- API reference: `docs/api-reference.md`
- [Laravel PDF Invoices](https://packages.akira-io.com/packages/laravel-pdf-invoices) - Generate professional PDF invoices automatically
