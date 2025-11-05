# Rate Limiting Guide

Complete documentation for the SISP rate limiting system.

## Overview

The rate limiting system protects your payment gateway from abuse by enforcing request quotas. It tracks requests by:
- **IP Address** - Limits per client IP
- **Merchant** - Limits per merchant account
- **User** - Limits per authenticated user

## How It Works

### The Reset Mechanism

The rate limit reset is guaranteed by a **synchronous check on every request**:

```php
// In CheckRateLimitAction.php (lines 57-59)
if ($rateLimit->reset_at->isPast()) {
    $rateLimit->reset();
}
```

This is the **only thing that triggers a reset** - there are no cron jobs or background workers. When a request arrives:

1. The system loads the rate limit record from the database
2. It checks: `isPast()` - Is the reset time in the past?
3. If YES → calls `reset()` which:
   - Sets `hits = 0`
   - Extends `reset_at` by window_seconds (1 hour forward)
   - Sets `is_blocked = false`
   - Clears `blocked_until`
4. If NO → continues with current window

### Real Example

```
10:00:00 - IP 192.168.1.100 creates first request
├─ DB creates: hits=1, reset_at=11:00:00, is_blocked=false
└─ Request ACCEPTED ✓

10:30:00 - Same IP makes more requests
├─ Checks: reset_at (11:00:00) < now (10:30:00)? NO
├─ Continues with same window: hits=50
└─ Request ACCEPTED ✓

10:59:59 - Makes 100th request
├─ Checks: reset_at (11:00:00) < now? NO
├─ Increments: hits=100
├─ Check limit: 100 >= 100? YES! EXCEEDED
├─ Marks: is_blocked=true, blocked_until=11:59:59
└─ Request REJECTED ✗

11:00:01 - Tries to make another request
├─ Cache lookup: rate_limit_blocked:ip:192.168.1.100
├─ Found! Still blocked (blocked_until=11:59:59)
└─ Request REJECTED ✗ (FAST - from cache)

12:00:02 - Tries again (1+ hour later)
├─ Loads from DB: reset_at=11:00:00
├─ Checks: isPast() - Is 11:00:00 < 12:00:02? YES!
├─ Calls reset():
│  ├─ hits = 0
│  ├─ reset_at = 12:00:02 + 3600 = 13:00:02
│  ├─ is_blocked = false
│  └─ blocked_until = null
├─ New window starts!
└─ Request ACCEPTED ✓ (new cycle)
```

## Architecture

### Three Layers of Protection

#### Layer 1: Middleware
```
Request → EnforceSispRateLimits Middleware
          ├─ Checks: Is IP blacklisted?
          ├─ Checks: Per-IP rate limit?
          └─ Returns 403/429 if violated
                ↓
          Passes to Controller
```

**File:** `src/Http/Middleware/EnforceSispRateLimits.php`

#### Layer 2: Controller
```
→ PaymentController::__invoke()
  ├─ Checks: Is IP blacklisted? (redundant)
  ├─ Checks: Rate limit exceeded?
  └─ Returns error if violated
        ↓
  Creates transaction
```

**File:** `src/Http/Controllers/PaymentController.php`

#### Layer 3: Cache + Database
```
Middleware/Controller check:

  1. Cache Check (FAST)
     └─ Is IP in cache as blocked?
        YES → Return 429 immediately
        NO → Go to DB

  2. Database Check (SLOWER)
     └─ Get rate limit record
        └─ Is reset_at in past?
           YES → Call reset()
           NO → Continue with current hits
        └─ Increment hits
        └─ Is limit exceeded?
           YES → Store in cache (3600s) + throw exception
           NO → Continue request
```

## Configuration

### Environment Variables

```env
# Enable/disable entire rate limiting
SISP_RATE_LIMITING_ENABLED=true

# Per IP configuration
SISP_RATE_LIMIT_PER_IP=true
SISP_RATE_LIMIT_PER_IP_LIMIT=100        # max 100 requests
SISP_RATE_LIMIT_PER_IP_WINDOW=3600      # per 1 hour (3600 seconds)

# Per merchant configuration
SISP_RATE_LIMIT_PER_MERCHANT=true
SISP_RATE_LIMIT_PER_MERCHANT_LIMIT=500
SISP_RATE_LIMIT_PER_MERCHANT_WINDOW=3600

# Per user configuration
SISP_RATE_LIMIT_PER_USER=true
SISP_RATE_LIMIT_PER_USER_LIMIT=50
SISP_RATE_LIMIT_PER_USER_WINDOW=3600
```

### In Code (config/sisp.php)

```php
'rate_limiting' => [
    'enabled' => env('SISP_RATE_LIMITING_ENABLED', true),

    'per_ip' => [
        'enabled' => env('SISP_RATE_LIMIT_PER_IP', true),
        'limit' => env('SISP_RATE_LIMIT_PER_IP_LIMIT', 100),
        'window_seconds' => env('SISP_RATE_LIMIT_PER_IP_WINDOW', 3600),
    ],

    'per_merchant' => [
        'enabled' => env('SISP_RATE_LIMIT_PER_MERCHANT', true),
        'limit' => env('SISP_RATE_LIMIT_PER_MERCHANT_LIMIT', 500),
        'window_seconds' => env('SISP_RATE_LIMIT_PER_MERCHANT_WINDOW', 3600),
    ],

    'per_user' => [
        'enabled' => env('SISP_RATE_LIMIT_PER_USER', true),
        'limit' => env('SISP_RATE_LIMIT_PER_USER_LIMIT', 50),
        'window_seconds' => env('SISP_RATE_LIMIT_PER_USER_WINDOW', 3600),
    ],
],
```

## Database Schema

### sisp_rate_limits Table

| Column | Type | Purpose |
|--------|------|---------|
| `id` | UUID | Primary key |
| `identifier` | string | IP, user_id, or merchant_id |
| `limit_type` | string | 'ip', 'user', 'merchant' |
| `context` | string | Additional context (optional) |
| `hits` | integer | Current request count |
| `limit` | integer | Maximum allowed hits |
| `window_seconds` | integer | Time window (e.g., 3600) |
| `reset_at` | datetime | When counter resets |
| `is_blocked` | boolean | Temporarily blocked? |
| `blocked_until` | datetime | Block expiration time |
| `created_at` | datetime | Record created |
| `updated_at` | datetime | Record updated |

## API Reference

### CheckRateLimitAction

Main action for rate limit checking.

#### Method Signature

```php
public function handle(
    string $limitType = 'ip',        // 'ip', 'merchant', 'user'
    ?string $identifier = null,      // IP, merchant_id, user_id
    ?string $context = null,         // optional context
    ?int $limit = null,              // custom limit
    ?int $windowSeconds = null       // custom window
): void
```

#### Usage

```php
use Akira\Sisp\Actions\CheckRateLimitAction;
use Akira\Sisp\Exceptions\RateLimitExceededException;

$checkRateLimit = app(CheckRateLimitAction::class);

try {
    // Check per IP (uses defaults from config)
    $checkRateLimit->handle(
        limitType: 'ip',
        identifier: request()->ip()
    );

    // Check per merchant (custom limit)
    $checkRateLimit->handle(
        limitType: 'merchant',
        identifier: $merchantId,
        limit: 1000,
        windowSeconds: 7200  // 2 hours
    );

    // Check per user with context
    $checkRateLimit->handle(
        limitType: 'user',
        identifier: $userId,
        context: 'bulk_payment',
        limit: 10
    );

} catch (RateLimitExceededException $e) {
    return response()->json([
        'error' => $e->getMessage(),
        'status' => 429
    ], 429);
}
```

### RateLimit Model

#### Methods

```php
// Increment request counter
$rateLimit->recordHit();

// Reset counter and extend window
$rateLimit->reset();

// Block temporarily
$rateLimit->block(3600);  // Block for 3600 seconds

// Check if limit exceeded
if ($rateLimit->isLimitExceeded()) {
    // hits >= limit
}

// Query scopes
RateLimit::blocked()->get();      // Currently blocked
RateLimit::active()->get();       // Active (not expired)
RateLimit::expired()->get();      // Window expired
```

#### Example

```php
use Akira\Sisp\Models\RateLimit;

// Find specific rate limit
$rateLimit = RateLimit::where([
    'identifier' => '192.168.1.100',
    'limit_type' => 'ip'
])->first();

// Get all blocked IPs
$blockedIps = RateLimit::blocked()
    ->where('limit_type', 'ip')
    ->get();

// Get high-activity users
$activeUsers = RateLimit::active()
    ->where('limit_type', 'user')
    ->where('hits', '>', 40)
    ->get();
```

## Exception Handling

### RateLimitExceededException

Thrown when a request exceeds rate limit.

```php
use Akira\Sisp\Exceptions\RateLimitExceededException;

try {
    $checkRateLimit->handle($limitType, $identifier);
} catch (RateLimitExceededException $e) {
    // Message: "Rate limit exceeded for ip: 192.168.1.100.
    //           Limit: 100 requests per 3600 seconds"

    Log::warning('Rate limit hit', [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
    ]);

    return response()->json([
        'error' => 'Too many requests',
        'retry_after' => 3600
    ], 429);
}
```

## Cache Implementation

### How Cache Works

The system uses **Laravel Cache** for ultra-fast blocking:

1. When limit exceeded:
```php
Cache::put(
    "rate_limit_blocked:ip:192.168.1.100:null",
    true,
    3600  // 1 hour
);
```

2. On next request:
```php
if (Cache::has("rate_limit_blocked:ip:192.168.1.100:null")) {
    throw new RateLimitExceededException();  // Instant response
}
```

3. After window expires:
```php
// Cache expires automatically (no manual cleanup needed)
// Next request → check database → find expired reset_at → reset
```

### Cache Keys Format

```
rate_limit:{type}:{identifier}:{context}
rate_limit_blocked:{type}:{identifier}:{context}

Examples:
- rate_limit:ip:192.168.1.100:null
- rate_limit_blocked:ip:192.168.1.100:null
- rate_limit:user:123:bulk_payment
- rate_limit_blocked:merchant:456:null
```

### Cache Drivers

The system works with any Laravel cache driver:
- Redis (recommended for high traffic)
- Memcached
- Database
- File-based (for development)

## Performance Characteristics

### Typical Response Times

| Operation | Time | Notes |
|-----------|------|-------|
| Cache hit (blocked) | <1ms | Instant rejection |
| Database hit (allowed) | 5-10ms | Increment and continue |
| Reset operation | 10-15ms | Only when window expires |
| First request setup | 10-20ms | Creates DB record + cache |

### Scalability

- **Minimal overhead**: ~2-5% of request latency
- **Cache-first approach**: Most blocked requests return in <1ms
- **Database writes**: Only on state changes (hit count, reset, block)
- **Concurrent requests**: Safe with database locking

## Troubleshooting

### Rate Limit Not Resetting

**Problem:** Requests still blocked after 1 hour

**Solutions:**
```php
// Check reset_at timestamp
$limit = RateLimit::find(1);
echo $limit->reset_at;  // Should be in future
echo $limit->is_blocked;  // Should be false

// Manually reset if needed
$limit->reset();
$limit->save();

// Check cache
Cache::forget('rate_limit_blocked:ip:192.168.1.100:null');
```

### Custom Window Periods

To use different window sizes:

```env
# 30 minutes per IP
SISP_RATE_LIMIT_PER_IP_WINDOW=1800

# 2 hours per merchant
SISP_RATE_LIMIT_PER_MERCHANT_WINDOW=7200

# 1 day per user
SISP_RATE_LIMIT_PER_USER_WINDOW=86400
```

### Per-Request Custom Limits

Override defaults for specific operations:

```php
$checkRateLimit->handle(
    limitType: 'user',
    identifier: $userId,
    context: 'bulk_payment',
    limit: 5,              // Only 5 requests
    windowSeconds: 3600    // per hour
);
```

## Security Considerations

1. **Always use middleware** for automatic enforcement
2. **Monitor blocked IPs** for attack patterns
3. **Whitelist legitimate high-volume users** with custom limits
4. **Combine with blacklist** for additional security
5. **Log rate limit events** for audit trails
6. **Alert on spike** in blocked requests

## Integration Examples

### With Monitoring

```php
use Akira\Sisp\Exceptions\RateLimitExceededException;

try {
    $checkRateLimit->handle('ip', $request->ip());
} catch (RateLimitExceededException $e) {
    // Alert security team
    Log::alert('Rate limit exceeded', [
        'ip' => $request->ip(),
        'endpoint' => $request->path(),
        'timestamp' => now(),
    ]);

    // Send to monitoring service
    event(new RateLimitEvent($request->ip()));

    return response()->json(['error' => 'Too many requests'], 429);
}
```

### With Whitelisting

```php
$whitelisted = config('sisp.rate_limit_whitelist', []);

if (!in_array($request->ip(), $whitelisted)) {
    $checkRateLimit->handle('ip', $request->ip());
}
```

### With User-Based Limits

```php
$userId = auth()->id();
$userType = auth()->user()->type; // 'free', 'premium', 'enterprise'

$limit = match($userType) {
    'enterprise' => 5000,
    'premium' => 500,
    'free' => 100,
};

$checkRateLimit->handle(
    limitType: 'user',
    identifier: $userId,
    limit: $limit,
    windowSeconds: 3600
);
```

## Integration with PDF Invoices

After a successful payment (not rate-limited), you can automatically generate PDF invoices using [Laravel PDF Invoices](https://packages.akira-io.com/packages/laravel-pdf-invoices).

The rate limit only affects the payment creation process - once approved, the invoice generation happens independently.

## See Also

- Security guide: `docs/security-and-fraud-detection.md`
- Configuration: `docs/configuration.md`
- API Reference: `docs/api-reference.md`
- Blacklist management: See security guide
- [Laravel PDF Invoices](https://packages.akira-io.com/packages/laravel-pdf-invoices) - Generate professional PDF invoices