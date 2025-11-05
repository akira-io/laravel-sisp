# Events & Monitoring

Guide to listening to security events and setting up real-time monitoring for the SISP payment system.

## Event Listeners

### Listen to Rate Limit Exceeded

Create an event listener for rate limit violations:

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use Akira\Sisp\Events\RateLimitExceeded;
use Illuminate\Support\Facades\Log;
use Notification;

final readonly class OnRateLimitExceeded
{
    public function handle(RateLimitExceeded $event): void
    {
        Log::warning('Rate limit exceeded', [
            'identifier' => $event->identifier,
            'limit_type' => $event->limitType,
            'ip' => $event->ip,
            'timestamp' => now(),
        ]);

        // Send notification to security team
        // Notification::route('mail', 'security@example.com')
        //     ->notify(new RateLimitNotification($event));
    }
}
```

Register in `app/Providers/EventServiceProvider.php`:

```php
<?php

use App\Listeners\OnRateLimitExceeded;
use Akira\Sisp\Events\RateLimitExceeded;

protected $listen = [
    RateLimitExceeded::class => [
        OnRateLimitExceeded::class,
    ],
];
```

### Listen to Blacklist Violations

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use Akira\Sisp\Events\BlacklistedIdentifierAccessed;
use Illuminate\Support\Facades\Log;

final readonly class OnBlacklistedAccess
{
    public function handle(BlacklistedIdentifierAccessed $event): void
    {
        Log::alert('Blacklisted identifier attempted access', [
            'type' => $event->type,
            'value' => $event->value,
            'reason' => $event->reason,
            'ip' => request()->ip(),
        ]);

        // Alert security team immediately
        // Send to monitoring service
        // Create incident ticket
    }
}
```

### Listen to High-Risk Transactions

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use Akira\Sisp\Models\RequestMetadata;
use Illuminate\Support\Facades\Log;

final readonly class OnHighRiskTransaction
{
    public function handle(RequestMetadata $metadata): void
    {
        if ($metadata->risk_score >= 70) {
            Log::alert('Critical risk transaction detected', [
                'transaction_id' => $metadata->transaction_id,
                'risk_score' => $metadata->risk_score,
                'risk_reason' => $metadata->risk_reason,
                'ip' => $metadata->ip_address,
                'country' => $metadata->country_code,
                'is_vpn' => $metadata->is_vpn,
                'is_proxy' => $metadata->is_proxy,
            ]);
        }
    }
}
```

## Webhooks & External Notifications

### Send to Slack

```php
<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\SlackMessage;

final class RateLimitNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['slack'];
    }

    public function toSlack($notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->error()
            ->content('Rate Limit Exceeded')
            ->attachment(function ($attachment) {
                $attachment
                    ->title('Rate Limit Details')
                    ->fields([
                        'Identifier' => $this->event->identifier,
                        'Type' => $this->event->limitType,
                        'IP' => $this->event->ip,
                        'Time' => now()->toDateTimeString(),
                    ]);
            });
    }
}
```

Use in listener:

```php
<?php

use App\Notifications\RateLimitNotification;
use Notification;

Notification::route('slack', config('sisp.slack_webhook'))
    ->notify(new RateLimitNotification($event));
```

### Send to External Service

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Akira\Sisp\Models\RequestMetadata;

final readonly class ExternalMonitoringService
{
    public function reportHighRisk(RequestMetadata $metadata): void
    {
        Http::post('https://monitoring.example.com/api/alerts', [
            'type' => 'high_risk_transaction',
            'severity' => 'high',
            'data' => [
                'transaction_id' => $metadata->transaction_id,
                'risk_score' => $metadata->risk_score,
                'risk_reason' => $metadata->risk_reason,
                'ip' => $metadata->ip_address,
                'country' => $metadata->country_code,
                'timestamp' => $metadata->created_at,
            ],
        ]);
    }

    public function reportRateLimitViolation(string $ip, int $hits, int $limit): void
    {
        Http::post('https://monitoring.example.com/api/alerts', [
            'type' => 'rate_limit_exceeded',
            'severity' => 'medium',
            'data' => [
                'ip' => $ip,
                'hits' => $hits,
                'limit' => $limit,
                'timestamp' => now(),
            ],
        ]);
    }
}
```

## Monitoring Dashboard Commands

### Create Console Commands for Monitoring

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Akira\Sisp\Models\RequestMetadata;
use Akira\Sisp\Models\RateLimit;
use Akira\Sisp\Transaction;
use Illuminate\Console\Command;

final class SispSecurityDashboard extends Command
{
    protected $signature = 'sisp:security-dashboard';
    protected $description = 'Display current security metrics';

    public function handle(): int
    {
        $this->info('=== SISP Security Dashboard ===');
        $this->newLine();

        // Transaction Stats
        $totalTransactions = Transaction::count();
        $todayTransactions = Transaction::where('created_at', '>', now()->startOfDay())->count();

        $this->info('Transaction Stats:');
        $this->line("  Total: {$totalTransactions}");
        $this->line("  Today: {$todayTransactions}");
        $this->newLine();

        // Risk Stats
        $highRisk = RequestMetadata::where('risk_score', '>', 60)->count();
        $criticalRisk = RequestMetadata::where('risk_score', '>', 80)->count();

        $this->warn('Risk Stats:');
        $this->line("  High Risk (60+): {$highRisk}");
        $this->line("  Critical (80+): {$criticalRisk}");
        $this->newLine();

        // VPN/Proxy Usage
        $vpnUsers = RequestMetadata::where('is_vpn', true)->count();
        $proxyUsers = RequestMetadata::where('is_proxy', true)->count();

        $this->info('VPN/Proxy Detection:');
        $this->line("  VPN Users: {$vpnUsers}");
        $this->line("  Proxy Users: {$proxyUsers}");
        $this->newLine();

        // Rate Limiting
        $blockedIps = RateLimit::where('is_blocked', true)->count();
        $activeRateLimits = RateLimit::where('reset_at', '>', now())->count();

        $this->comment('Rate Limiting:');
        $this->line("  Blocked IPs: {$blockedIps}");
        $this->line("  Active Limits: {$activeRateLimits}");
        $this->newLine();

        // Top Countries
        $this->info('Top Countries:');
        RequestMetadata::selectRaw('country_code, country_name, COUNT(*) as count')
            ->groupBy('country_code', 'country_name')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->each(fn ($row) => $this->line("  {$row->country_code}: {$row->count}"));

        return self::SUCCESS;
    }
}
```

Run it:

```bash
php artisan sisp:security-dashboard
```

### Real-time Rate Limit Monitor

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Akira\Sisp\Models\RateLimit;
use Illuminate\Console\Command;

final class MonitorRateLimits extends Command
{
    protected $signature = 'sisp:monitor-rate-limits {--interval=5}';
    protected $description = 'Monitor rate limits in real-time';

    public function handle(): int
    {
        $interval = (int)$this->option('interval');

        $this->info('Monitoring rate limits (refreshing every '.$interval.'s)...');
        $this->info('Press Ctrl+C to stop');

        while (true) {
            system('clear');
            $this->displayStats();
            sleep($interval);
        }
    }

    private function displayStats(): void
    {
        $this->info('=== Rate Limit Monitor ===');
        $this->newLine();

        // Blocked IPs
        $blocked = RateLimit::where('is_blocked', true)
            ->where(function ($q) {
                $q->whereNull('blocked_until')
                    ->orWhere('blocked_until', '>', now());
            })
            ->orderByDesc('updated_at')
            ->take(10)
            ->get();

        if ($blocked->count() > 0) {
            $this->warn("Blocked IPs ({$blocked->count()}): ");
            $blocked->each(function ($limit) {
                $expiresIn = $limit->blocked_until
                    ? now()->diffInMinutes($limit->blocked_until)
                    : 'Permanent';
                $this->line("  {$limit->identifier}: Expires in {$expiresIn}");
            });
        } else {
            $this->info('No blocked IPs');
        }

        $this->newLine();

        // Active limits near threshold
        $nearLimit = RateLimit::where('reset_at', '>', now())
            ->whereRaw('hits >= (limit * 0.8)')
            ->orderByDesc('hits')
            ->take(10)
            ->get();

        if ($nearLimit->count() > 0) {
            $this->comment("Approaching Limits ({$nearLimit->count()}): ");
            $nearLimit->each(function ($limit) {
                $percent = round(($limit->hits / $limit->limit) * 100);
                $this->line("  {$limit->identifier}: {$limit->hits}/{$limit->limit} ({$percent}%)");
            });
        }

        $this->newLine();
        $this->info('Last updated: '.now()->toDateTimeString());
    }
}
```

### High Risk Transaction Alert Command

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Akira\Sisp\Models\RequestMetadata;
use Illuminate\Console\Command;

final class CheckHighRiskTransactions extends Command
{
    protected $signature = 'sisp:check-high-risk {--threshold=70} {--minutes=60}';
    protected $description = 'Check for high-risk transactions in the last N minutes';

    public function handle(): int
    {
        $threshold = (int)$this->option('threshold');
        $minutes = (int)$this->option('minutes');

        $highRisk = RequestMetadata::where('risk_score', '>', $threshold)
            ->where('created_at', '>', now()->subMinutes($minutes))
            ->orderByDesc('risk_score')
            ->with('transaction')
            ->get();

        if ($highRisk->isEmpty()) {
            $this->info('No high-risk transactions found');
            return self::SUCCESS;
        }

        $this->error("Found {$highRisk->count()} high-risk transactions:");
        $this->newLine();

        $highRisk->each(function ($metadata) {
            $this->line("Transaction ID: {$metadata->transaction_id}");
            $this->line("  Risk Score: {$metadata->risk_score}");
            $this->line("  Reason: {$metadata->risk_reason}");
            $this->line("  IP: {$metadata->ip_address}");
            $this->line("  Country: {$metadata->country_code}");
            $this->line("  VPN: ".($metadata->is_vpn ? 'Yes' : 'No'));
            $this->line("  Proxy: ".($metadata->is_proxy ? 'Yes' : 'No'));
            $this->newLine();
        });

        return self::SUCCESS;
    }
}
```

## Scheduled Monitoring

### Set Up Automated Checks in Kernel

In `app/Console/Kernel.php`:

```php
<?php

protected function schedule(Schedule $schedule)
{
    // Check high-risk transactions every 15 minutes
    $schedule->command('sisp:check-high-risk --threshold=70 --minutes=15')
        ->everyFifteenMinutes()
        ->onFailure(function () {
            // Alert if command fails
            Log::error('High-risk check failed');
        });

    // Check for VPN/Proxy abuse every hour
    $schedule->call(function () {
        $vpnCount = RequestMetadata::where('is_vpn', true)
            ->where('created_at', '>', now()->subHour())
            ->count();

        if ($vpnCount > 50) {
            Log::warning("High VPN usage detected: {$vpnCount} in last hour");
        }
    })->hourly();

    // Cleanup expired rate limits daily
    $schedule->call(function () {
        RateLimit::where('reset_at', '<', now())
            ->where('is_blocked', false)
            ->delete();
    })->daily();

    // Generate security report daily
    $schedule->command('sisp:security-report')
        ->daily()
        ->at('09:00');
}
```

## Alert Rules

### Define Custom Alert Rules

```php
<?php

declare(straight_types=1);

namespace App\Services;

use Akira\Sisp\Models\RequestMetadata;
use Akira\Sisp\Models\RateLimit;
use Illuminate\Support\Facades\Log;

final readonly class AlertRuleEngine
{
    public function checkAllRules(): void
    {
        $this->checkExcessiveVpnUsage();
        $this->checkHighVelocityPayments();
        $this->checkGeographicSpikes();
        $this->checkRateLimitViolations();
        $this->checkAnomalousAmounts();
    }

    private function checkExcessiveVpnUsage(): void
    {
        $vpnCountInLastHour = RequestMetadata::where('is_vpn', true)
            ->where('created_at', '>', now()->subHour())
            ->count();

        if ($vpnCountInLastHour > 50) {
            Log::alert('Excessive VPN usage detected', [
                'count' => $vpnCountInLastHour,
                'window' => '1 hour',
            ]);
        }
    }

    private function checkHighVelocityPayments(): void
    {
        $velocityByIp = RequestMetadata::selectRaw('ip_address, COUNT(*) as count')
            ->where('created_at', '>', now()->subMinutes(5))
            ->groupBy('ip_address')
            ->having('count', '>', 10)
            ->get();

        foreach ($velocityByIp as $item) {
            Log::alert('High velocity payments from single IP', [
                'ip' => $item->ip_address,
                'count' => $item->count,
                'window' => '5 minutes',
            ]);
        }
    }

    private function checkGeographicSpikes(): void
    {
        $normalCountries = RequestMetadata::selectRaw('country_code, AVG(daily_count) as avg')
            ->where('created_at', '>', now()->subDays(30))
            ->groupBy('country_code')
            ->having('avg', '>', 0)
            ->get()
            ->keyBy('country_code');

        $todayCountries = RequestMetadata::selectRaw('country_code, COUNT(*) as count')
            ->where('created_at', '>', now()->startOfDay())
            ->groupBy('country_code')
            ->get();

        foreach ($todayCountries as $today) {
            $normal = $normalCountries[$today->country_code] ?? null;
            if ($normal && $today->count > ($normal->avg * 3)) {
                Log::alert('Geographic spike detected', [
                    'country' => $today->country_code,
                    'today_count' => $today->count,
                    'normal_avg' => $normal->avg,
                ]);
            }
        }
    }

    private function checkRateLimitViolations(): void
    {
        $violations = RateLimit::where('is_blocked', true)
            ->where('blocked_until', '>', now())
            ->count();

        if ($violations > 10) {
            Log::warning('Multiple rate limit violations', [
                'blocked_count' => $violations,
            ]);
        }
    }

    private function checkAnomalousAmounts(): void
    {
        $avgAmount = RequestMetadata::selectRaw('AVG(transaction.amount) as avg')
            ->join('sisp_transactions', 'sisp_request_metadata.transaction_id', '=', 'sisp_transactions.id')
            ->where('sisp_request_metadata.created_at', '>', now()->subHours(24))
            ->value('avg');

        if ($avgAmount) {
            $outliers = RequestMetadata::selectRaw('transaction_id, amount')
                ->join('sisp_transactions', 'sisp_request_metadata.transaction_id', '=', 'sisp_transactions.id')
                ->where('sisp_request_metadata.created_at', '>', now()->subHour())
                ->whereRaw('amount > ?', [$avgAmount * 5])
                ->get();

            foreach ($outliers as $outlier) {
                Log::alert('Anomalous transaction amount', [
                    'transaction_id' => $outlier->transaction_id,
                    'amount' => $outlier->amount,
                    'avg_24h' => $avgAmount,
                ]);
            }
        }
    }
}
```

Schedule it:

```php
$schedule->call(function () {
    app(AlertRuleEngine::class)->checkAllRules();
})->everyFiveMinutes();
```

## See Also

- [Security & Fraud Detection](./security-and-fraud-detection.md) - Comprehensive fraud detection guide
- [Rate Limiting](./rate-limiting.md) - Rate limiting system documentation
- [Middleware & Security](./middleware-and-security.md) - Middleware security setup
- [Fraud Detection Analysis](./fraud-detection-analysis.md) - Detailed fraud analysis patterns
- [Configuration Reference](./configuration.md) - All configuration options
- [Payment Flow](./payment-flow.md) - Complete payment process overview
