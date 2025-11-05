# Middleware & Security Registration

Complete guide to registering and using SISP security middleware and actions in your Laravel application.

## Middleware Registration

### In Your Service Provider

Create a service provider to register the SISP middleware:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Akira\Sisp\Http\Middleware\EnforceSispRateLimits;
use Illuminate\Support\ServiceProvider;

final class SispServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register in container if needed
    }

    public function boot(): void
    {
        // Register middleware in HTTP kernel
    }
}
```

### In HTTP Kernel

Register the middleware in `app/Http/Kernel.php`:

```php
<?php

namespace App\Http;

use Akira\Sisp\Http\Middleware\EnforceSispRateLimits;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $routeMiddleware = [
        // ... other middleware
        'sisp.rate_limit' => EnforceSispRateLimits::class,
    ];
}
```

### Apply to Routes

Use the middleware on specific payment routes:

```php
<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'sisp.rate_limit'])->group(function () {
    Route::post('/payment', [PaymentController::class, 'store']);
    Route::post('/payment/callback', [PaymentController::class, 'callback']);
});
```

Or apply to entire route group:

```php
Route::middleware('sisp.rate_limit')
    ->prefix('payment')
    ->name('payment.')
    ->group(function () {
        Route::post('/', [PaymentController::class, 'store'])->name('store');
        Route::post('/callback', [PaymentController::class, 'callback'])->name('callback');
    });
```

## Security Checks in Controllers

### Check Blacklist Only

Block specific IPs or identifiers:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Akira\Sisp\Actions\CheckBlacklistAction;
use Illuminate\Http\Request;

final readonly class PaymentController
{
    public function __construct(
        private CheckBlacklistAction $checkBlacklist,
    ) {}

    public function initiatePayment(Request $request)
    {
        // Check if IP is blacklisted
        $this->checkBlacklist->handle('ip', $request->ip());

        // If we reach here, IP is not blacklisted
        return response()->json(['status' => 'allowed']);
    }
}
```

### Check Rate Limit Only

Enforce per-IP rate limiting:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Akira\Sisp\Actions\CheckRateLimitAction;
use Akira\Sisp\Configuration\LoadConfig;
use Illuminate\Http\Request;

final readonly class PaymentController
{
    public function __construct(
        private CheckRateLimitAction $checkRateLimit,
        private LoadConfig $config,
    ) {}

    public function initiatePayment(Request $request)
    {
        // Check rate limit using config values
        $this->checkRateLimit->handle(
            identifier: $request->ip(),
            limitType: 'ip',
            limit: $this->config->getRateLimitPerIp(),
            windowSeconds: $this->config->getRateLimitWindowSeconds(),
        );

        // If we reach here, rate limit not exceeded
        return response()->json(['status' => 'allowed']);
    }
}
```

### Check Both Blacklist and Rate Limit

Typical security flow:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Akira\Sisp\Actions\CheckBlacklistAction;
use Akira\Sisp\Actions\CheckRateLimitAction;
use Akira\Sisp\Configuration\LoadConfig;
use Illuminate\Http\Request;

final readonly class PaymentController
{
    public function __construct(
        private CheckBlacklistAction $checkBlacklist,
        private CheckRateLimitAction $checkRateLimit,
        private LoadConfig $config,
    ) {}

    public function initiatePayment(Request $request)
    {
        $ip = $request->ip();

        // First check blacklist (faster, no increment)
        $this->checkBlacklist->handle('ip', $ip);

        // Then check rate limit (increments counter)
        $this->checkRateLimit->handle(
            identifier: $ip,
            limitType: 'ip',
            limit: $this->config->getRateLimitPerIp(),
            windowSeconds: $this->config->getRateLimitWindowSeconds(),
        );

        return response()->json(['status' => 'allowed']);
    }
}
```

### Store Request Metadata

Track comprehensive request information:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Akira\Sisp\Actions\StoreRequestMetadataAction;
use Akira\Sisp\Transaction;
use Illuminate\Http\Request;

final readonly class PaymentController
{
    public function __construct(
        private StoreRequestMetadataAction $storeMetadata,
    ) {}

    public function completePayment(Request $request, Transaction $transaction)
    {
        // Store metadata for fraud detection
        $metadata = $this->storeMetadata->handle($request, $transaction);

        // Access metadata properties
        echo $metadata->ip_address;      // 192.168.1.1
        echo $metadata->country_code;   // CV
        echo $metadata->device_type;    // mobile
        echo $metadata->is_vpn;         // false
        echo $metadata->risk_score;     // 25

        return response()->json($transaction);
    }
}
```

## Customizing Security Checks

### Per-User Rate Limiting

Rate limit based on authenticated user:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Akira\Sisp\Actions\CheckRateLimitAction;
use Illuminate\Http\Request;

final readonly class PaymentController
{
    public function __construct(
        private CheckRateLimitAction $checkRateLimit,
    ) {}

    public function initiatePayment(Request $request)
    {
        $user = $request->user();

        // Rate limit per user (50 requests per hour)
        $this->checkRateLimit->handle(
            identifier: (string)$user->id,
            limitType: 'user',
            limit: 50,
            windowSeconds: 3600,
        );

        return response()->json(['status' => 'allowed']);
    }
}
```

### Per-Product Rate Limiting

Rate limit specific products:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Akira\Sisp\Actions\CheckRateLimitAction;
use Illuminate\Http\Request;

final readonly class PaymentController
{
    public function __construct(
        private CheckRateLimitAction $checkRateLimit,
    ) {}

    public function purchaseProduct(Request $request, string $productSku)
    {
        // Limit purchases of specific product (100 per day per IP)
        $this->checkRateLimit->handle(
            identifier: $request->ip(),
            limitType: 'product',
            context: $productSku,
            limit: 100,
            windowSeconds: 86400, // 24 hours
        );

        return response()->json(['status' => 'allowed']);
    }
}
```

### Per-Merchant Rate Limiting

Rate limit entire merchant:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Akira\Sisp\Actions\CheckRateLimitAction;
use Illuminate\Http\Request;

final readonly class PaymentController
{
    public function __construct(
        private CheckRateLimitAction $checkRateLimit,
    ) {}

    public function initiatePayment(Request $request, string $merchantId)
    {
        // Limit all merchant transactions (500 per hour)
        $this->checkRateLimit->handle(
            identifier: $merchantId,
            limitType: 'merchant',
            limit: 500,
            windowSeconds: 3600,
        );

        return response()->json(['status' => 'allowed']);
    }
}
```

## Silent Security Checks

Check without throwing exceptions:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Akira\Sisp\Actions\CheckBlacklistAction;
use Illuminate\Http\Request;
use Log;

final readonly class PaymentController
{
    public function __construct(
        private CheckBlacklistAction $checkBlacklist,
    ) {}

    public function initiatePayment(Request $request)
    {
        $ip = $request->ip();

        // Silent check (returns boolean, doesn't throw)
        if ($this->checkBlacklist->isBlacklisted('ip', $ip)) {
            Log::warning('Blacklisted IP attempted payment', ['ip' => $ip]);
            return response()->json(['error' => 'Access denied'], 403);
        }

        return response()->json(['status' => 'allowed']);
    }
}
```

## Managing Blacklist

### Add to Blacklist

Block an IP temporarily:

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Akira\Sisp\Actions\CheckBlacklistAction;
use Illuminate\Console\Command;

final class BlacklistIpCommand extends Command
{
    protected $signature = 'sisp:blacklist {ip} {--duration=1440}';
    protected $description = 'Add IP to blacklist (default: 24 hours)';

    public function handle(CheckBlacklistAction $checkBlacklist): int
    {
        $ip = $this->argument('ip');
        $durationMinutes = (int)$this->option('duration');

        $checkBlacklist->add(
            type: 'ip',
            value: $ip,
            severity: 'high',
            reason: 'Manually blacklisted',
            addedBy: auth()->user()?->email ?? 'system',
            expiresInMinutes: $durationMinutes,
        );

        $this->info("IP {$ip} blacklisted for {$durationMinutes} minutes");
        return self::SUCCESS;
    }
}
```

### Permanently Block

Block an IP forever:

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Akira\Sisp\Actions\CheckBlacklistAction;
use Illuminate\Console\Command;

final class PermanentlyBlockIpCommand extends Command
{
    protected $signature = 'sisp:block-permanently {ip} {--reason=}';
    protected $description = 'Permanently block an IP';

    public function handle(CheckBlacklistAction $checkBlacklist): int
    {
        $ip = $this->argument('ip');
        $reason = $this->option('reason') ?? 'Permanent block';

        $checkBlacklist->add(
            type: 'ip',
            value: $ip,
            severity: 'critical',
            reason: $reason,
            addedBy: auth()->user()?->email ?? 'system',
            expiresInMinutes: null, // No expiration
        );

        $this->info("IP {$ip} permanently blocked");
        return self::SUCCESS;
    }
}
```

### Remove from Blacklist

Whitelist a previously blocked IP:

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Akira\Sisp\Actions\CheckBlacklistAction;
use Illuminate\Console\Command;

final class WhitelistIpCommand extends Command
{
    protected $signature = 'sisp:whitelist {ip}';
    protected $description = 'Remove IP from blacklist';

    public function handle(CheckBlacklistAction $checkBlacklist): int
    {
        $ip = $this->argument('ip');

        $checkBlacklist->remove('ip', $ip);

        $this->info("IP {$ip} removed from blacklist");
        return self::SUCCESS;
    }
}
```

### Block by Email

Block payments from specific email:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Akira\Sisp\Actions\CheckBlacklistAction;
use Illuminate\Http\Request;

final readonly class PaymentController
{
    public function __construct(
        private CheckBlacklistAction $checkBlacklist,
    ) {}

    public function initiatePayment(Request $request)
    {
        $email = $request->input('email');

        // Check if email is blacklisted
        $this->checkBlacklist->handle('email', $email);

        // Check if IP is blacklisted
        $this->checkBlacklist->handle('ip', $request->ip());

        return response()->json(['status' => 'allowed']);
    }
}
```

### Block by Device Fingerprint

Block suspicious devices:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Akira\Sisp\Actions\CheckBlacklistAction;
use Akira\Sisp\Actions\StoreRequestMetadataAction;
use Akira\Sisp\Transaction;
use Illuminate\Http\Request;

final readonly class PaymentController
{
    public function __construct(
        private CheckBlacklistAction $checkBlacklist,
        private StoreRequestMetadataAction $storeMetadata,
    ) {}

    public function processPayment(Request $request, Transaction $transaction)
    {
        // Store metadata to get device fingerprint
        $metadata = $this->storeMetadata->handle($request, $transaction);

        // Check if this device fingerprint is blacklisted
        $this->checkBlacklist->handle('device_fingerprint', $metadata->device_fingerprint);

        return response()->json(['status' => 'completed']);
    }
}
```

## Exception Handling

### Handle Rate Limit Exception

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Akira\Sisp\Actions\CheckRateLimitAction;
use Akira\Sisp\Exceptions\RateLimitExceededException;
use Illuminate\Http\Request;

final readonly class PaymentController
{
    public function __construct(
        private CheckRateLimitAction $checkRateLimit,
    ) {}

    public function initiatePayment(Request $request)
    {
        try {
            $this->checkRateLimit->handle(
                identifier: $request->ip(),
                limitType: 'ip',
            );
        } catch (RateLimitExceededException $e) {
            return response()->json([
                'error' => 'Too many requests',
                'message' => $e->getMessage(),
                'retry_after' => $e->getRetryAfter(),
            ], 429);
        }

        return response()->json(['status' => 'allowed']);
    }
}
```

### Handle Blacklist Exception

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Akira\Sisp\Actions\CheckBlacklistAction;
use Akira\Sisp\Exceptions\BlacklistedIdentifierException;
use Illuminate\Http\Request;
use Log;

final readonly class PaymentController
{
    public function __construct(
        private CheckBlacklistAction $checkBlacklist,
    ) {}

    public function initiatePayment(Request $request)
    {
        try {
            $this->checkBlacklist->handle('ip', $request->ip());
        } catch (BlacklistedIdentifierException $e) {
            Log::warning('Blocked identifier attempted access', [
                'type' => $e->getType(),
                'value' => $e->getValue(),
                'reason' => $e->getReason(),
            ]);

            return response()->json([
                'error' => 'Access denied',
                'reason' => $e->getReason(),
            ], 403);
        }

        return response()->json(['status' => 'allowed']);
    }
}
```

### Handle Both Exceptions

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Akira\Sisp\Actions\CheckBlacklistAction;
use Akira\Sisp\Actions\CheckRateLimitAction;
use Akira\Sisp\Exceptions\BlacklistedIdentifierException;
use Akira\Sisp\Exceptions\RateLimitExceededException;
use Illuminate\Http\Request;

final readonly class PaymentController
{
    public function __construct(
        private CheckBlacklistAction $checkBlacklist,
        private CheckRateLimitAction $checkRateLimit,
    ) {}

    public function initiatePayment(Request $request)
    {
        try {
            $this->checkBlacklist->handle('ip', $request->ip());
            $this->checkRateLimit->handle(
                identifier: $request->ip(),
                limitType: 'ip',
            );
        } catch (BlacklistedIdentifierException $e) {
            return response()->json(['error' => 'Access denied'], 403);
        } catch (RateLimitExceededException $e) {
            return response()->json(['error' => 'Too many requests'], 429);
        }

        return response()->json(['status' => 'allowed']);
    }
}
```

## Configuration

All security features are controlled via environment variables:

```env
# Metadata Collection
SISP_COLLECT_METADATA=true

# Rate Limiting
SISP_RATE_LIMITING_ENABLED=true
SISP_RATE_LIMIT_PER_IP=100
SISP_RATE_LIMIT_PER_IP_WINDOW_SECONDS=3600

# Security Features
SISP_DETECT_VPN=true
SISP_DETECT_PROXY=true
SISP_CALCULATE_RISK_SCORE=true
SISP_BLOCK_VPN_PROXY=false
SISP_BLOCK_NEW_COUNTRY_PAYMENTS=false

# Geolocation
SISP_GEOLOCATION_PROVIDER=maxmind
MAXMIND_KEY=your_key_here
SISP_GEOLOCATION_CACHE_TTL=1440
```

Disable security features by setting environment variables to false.

## Testing Security Features

### Test Rate Limiting

```php
<?php

namespace Tests\Feature;

use Akira\Sisp\Actions\CheckRateLimitAction;
use Akira\Sisp\Exceptions\RateLimitExceededException;
use Tests\TestCase;

final class RateLimitingTest extends TestCase
{
    public function test_rate_limit_is_enforced(): void
    {
        $checkRateLimit = app(CheckRateLimitAction::class);
        $ip = '192.168.1.1';

        // Make 100 requests
        for ($i = 0; $i < 100; $i++) {
            $checkRateLimit->handle(
                identifier: $ip,
                limitType: 'ip',
                limit: 100,
                windowSeconds: 3600,
            );
        }

        // 101st request should fail
        $this->expectException(RateLimitExceededException::class);

        $checkRateLimit->handle(
            identifier: $ip,
            limitType: 'ip',
            limit: 100,
            windowSeconds: 3600,
        );
    }
}
```

### Test Blacklist

```php
<?php

namespace Tests\Feature;

use Akira\Sisp\Actions\CheckBlacklistAction;
use Akira\Sisp\Exceptions\BlacklistedIdentifierException;
use Tests\TestCase;

final class BlacklistTest extends TestCase
{
    public function test_blacklisted_ip_is_blocked(): void
    {
        $checkBlacklist = app(CheckBlacklistAction::class);
        $ip = '203.0.113.1';

        // Add to blacklist
        $checkBlacklist->add(
            type: 'ip',
            value: $ip,
            severity: 'high',
            reason: 'Testing',
            addedBy: 'test',
        );

        // Trying to handle should throw exception
        $this->expectException(BlacklistedIdentifierException::class);

        $checkBlacklist->handle('ip', $ip);
    }
}
```

## See Also

- [Configuration Reference](./configuration.md) - All configuration options
- [Security & Fraud Detection](./security-and-fraud-detection.md) - Comprehensive fraud detection guide
- [API Reference](./api-reference.md) - Complete API documentation
- [Rate Limiting](./rate-limiting.md) - Rate limiting system
- [Events & Monitoring](./events-and-monitoring.md) - Monitoring and alerting
- [Fraud Detection Analysis](./fraud-detection-analysis.md) - Detailed fraud analysis patterns
