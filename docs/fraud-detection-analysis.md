# Fraud Detection & Metadata Analysis

Guide to analyzing request metadata and implementing fraud detection patterns.

## Basic Metadata Queries

### Get All Metadata for a Transaction

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Akira\Sisp\Models\RequestMetadata;
use Akira\Sisp\Transaction;

final readonly class MetadataService
{
    public function getTransactionMetadata(Transaction $transaction): RequestMetadata|null
    {
        return RequestMetadata::where('transaction_id', $transaction->id)->first();
    }
}
```

### Get Metadata for Multiple Transactions

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Akira\Sisp\Models\RequestMetadata;
use Illuminate\Database\Eloquent\Collection;

final readonly class MetadataService
{
    public function getMetadataByUser(int $userId): Collection
    {
        return RequestMetadata::whereHas('transaction', function ($query) {
            $query->where('user_id', $query->getModel()->getKeyName());
        })
            ->orderByDesc('created_at')
            ->get();
    }
}
```

### Get Recent Metadata

```php
<?php

$lastHour = RequestMetadata::where('created_at', '>', now()->subHour())
    ->orderByDesc('created_at')
    ->get();

$lastDay = RequestMetadata::where('created_at', '>', now()->subDay())
    ->orderByDesc('created_at')
    ->get();

$lastWeek = RequestMetadata::where('created_at', '>', now()->subWeek())
    ->orderByDesc('created_at')
    ->get();
```

## Risk Score Analysis

### Get High-Risk Transactions

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Akira\Sisp\Models\RequestMetadata;
use Illuminate\Database\Eloquent\Collection;

final readonly class RiskAnalysisService
{
    public function getHighRiskMetadata(int $threshold = 60): Collection
    {
        return RequestMetadata::where('risk_score', '>', $threshold)
            ->orderByDesc('risk_score')
            ->get();
    }

    public function getCriticalRiskMetadata(): Collection
    {
        return RequestMetadata::where('risk_score', '>', 80)
            ->orderByDesc('risk_score')
            ->get();
    }

    public function getMediumRiskMetadata(): Collection
    {
        return RequestMetadata::whereBetween('risk_score', [40, 60])
            ->orderByDesc('risk_score')
            ->get();
    }

    public function getLowRiskMetadata(): Collection
    {
        return RequestMetadata::where('risk_score', '<', 40)
            ->orderByDesc('risk_score')
            ->get();
    }
}
```

### Get Transactions by Risk Category

```php
<?php

$criticalRisk = RequestMetadata::where('risk_score', '>=', 61)->count();
$highRisk = RequestMetadata::whereBetween('risk_score', [41, 60])->count();
$mediumRisk = RequestMetadata::whereBetween('risk_score', [21, 40])->count();
$lowRisk = RequestMetadata::where('risk_score', '<=', 20)->count();

echo "Critical: {$criticalRisk}";
echo "High: {$highRisk}";
echo "Medium: {$mediumRisk}";
echo "Low: {$lowRisk}";
```

### Get Transactions with Specific Risk Reason

```php
<?php

$vpnUsage = RequestMetadata::where('risk_reason', 'like', '%VPN%')->get();
$geoAnomaly = RequestMetadata::where('risk_reason', 'like', '%Geographic%')->get();
$deviceChange = RequestMetadata::where('risk_reason', 'like', '%device%')->get();
$highVelocity = RequestMetadata::where('risk_reason', 'like', '%velocity%')->get();
```

## Geolocation Analysis

### Get Transactions by Country

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Akira\Sisp\Models\RequestMetadata;
use Illuminate\Database\Eloquent\Collection;

final readonly class GeoLocationService
{
    public function getTransactionsByCountry(string $countryCode): Collection
    {
        return RequestMetadata::where('country_code', $countryCode)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getCapeVerdeTransactions(): Collection
    {
        return $this->getTransactionsByCountry('CV');
    }

    public function getCountryDistribution(): array
    {
        return RequestMetadata::select('country_code', 'country_name')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('country_code', 'country_name')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }
}
```

### Detect Geographic Anomalies

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Akira\Sisp\Models\RequestMetadata;
use Illuminate\Support\Collection;

final readonly class GeoAnomalyDetector
{
    public function detectAnomalies(int $userId, int $hourLimit = 2): Collection
    {
        // Get last payment country
        $lastPayment = RequestMetadata::whereHas('transaction', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
            ->latest()
            ->first();

        if (!$lastPayment) {
            return collect();
        }

        $lastCountry = $lastPayment->country_code;
        $timeSinceLastPayment = now()->diffInHours($lastPayment->created_at);

        // If payment from different country within short time, it's suspicious
        return RequestMetadata::whereHas('transaction', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
            ->where('country_code', '!=', $lastCountry)
            ->where('created_at', '>', now()->subHours($hourLimit))
            ->get();
    }

    public function detectAnomalousCountries(int $userId): Collection
    {
        // Get user's typical countries
        $typicalCountries = RequestMetadata::whereHas('transaction', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
            ->selectRaw('country_code, COUNT(*) as count')
            ->groupBy('country_code')
            ->orderByDesc('count')
            ->limit(3)
            ->pluck('country_code')
            ->toArray();

        if (empty($typicalCountries)) {
            return collect();
        }

        // Find recent transactions from unusual countries
        return RequestMetadata::whereHas('transaction', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
            ->whereNotIn('country_code', $typicalCountries)
            ->where('created_at', '>', now()->subWeek())
            ->get();
    }
}
```

## Device & Browser Analysis

### Get Transactions by Device Type

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Akira\Sisp\Models\RequestMetadata;
use Illuminate\Database\Eloquent\Collection;

final readonly class DeviceAnalysisService
{
    public function getMobileTransactions(): Collection
    {
        return RequestMetadata::where('device_type', 'mobile')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getTabletTransactions(): Collection
    {
        return RequestMetadata::where('device_type', 'tablet')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getDesktopTransactions(): Collection
    {
        return RequestMetadata::where('device_type', 'desktop')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getDeviceDistribution(): array
    {
        return RequestMetadata::select('device_type')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('device_type')
            ->get()
            ->toArray();
    }

    public function getBrowserDistribution(): array
    {
        return RequestMetadata::select('browser')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('browser')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }
}
```

### Detect Device Changes

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Akira\Sisp\Models\RequestMetadata;
use Illuminate\Support\Collection;

final readonly class DeviceChangeDetector
{
    public function detectDeviceChanges(int $userId): Collection
    {
        // Get last device fingerprint
        $lastMetadata = RequestMetadata::whereHas('transaction', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
            ->latest()
            ->first();

        if (!$lastMetadata) {
            return collect();
        }

        $lastFingerprint = $lastMetadata->device_fingerprint;

        // Find different devices from same user
        return RequestMetadata::whereHas('transaction', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
            ->where('device_fingerprint', '!=', $lastFingerprint)
            ->where('created_at', '>', now()->subDays(30))
            ->orderByDesc('created_at')
            ->get();
    }

    public function getUniqueFingerprintsForUser(int $userId): int
    {
        return RequestMetadata::whereHas('transaction', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
            ->distinct('device_fingerprint')
            ->count();
    }

    public function getFingerprints(int $userId): Collection
    {
        return RequestMetadata::whereHas('transaction', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
            ->distinct('device_fingerprint')
            ->selectRaw('device_fingerprint, COUNT(*) as usage_count')
            ->groupBy('device_fingerprint')
            ->orderByDesc('usage_count')
            ->get();
    }
}
```

## VPN & Proxy Detection

### Get VPN Usage

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Akira\Sisp\Models\RequestMetadata;
use Illuminate\Database\Eloquent\Collection;

final readonly class VpnDetectionService
{
    public function getVpnTransactions(): Collection
    {
        return RequestMetadata::where('is_vpn', true)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getProxyTransactions(): Collection
    {
        return RequestMetadata::where('is_proxy', true)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getVpnOrProxyTransactions(): Collection
    {
        return RequestMetadata::where('is_vpn', true)
            ->orWhere('is_proxy', true)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getVpnCount(): int
    {
        return RequestMetadata::where('is_vpn', true)->count();
    }

    public function getProxyCount(): int
    {
        return RequestMetadata::where('is_proxy', true)->count();
    }

    public function getVpnPercentage(): float
    {
        $vpnCount = $this->getVpnCount();
        $total = RequestMetadata::count();

        return $total > 0 ? ($vpnCount / $total) * 100 : 0;
    }
}
```

## Velocity Analysis

### Detect High-Velocity Payments

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Akira\Sisp\Models\RequestMetadata;
use Akira\Sisp\Transaction;
use Illuminate\Support\Collection;

final readonly class VelocityAnalyzer
{
    public function getPaymentsInWindow(int $userId, int $minutes = 5): Collection
    {
        return Transaction::where('user_id', $userId)
            ->where('created_at', '>', now()->subMinutes($minutes))
            ->orderByDesc('created_at')
            ->get();
    }

    public function getPaymentVelocity(int $userId, int $minutes = 5): int
    {
        return $this->getPaymentsInWindow($userId, $minutes)->count();
    }

    public function isHighVelocity(int $userId, int $threshold = 5, int $minutes = 5): bool
    {
        return $this->getPaymentVelocity($userId, $minutes) > $threshold;
    }

    public function detectHighVelocityPayments(int $threshold = 5, int $minutes = 5): Collection
    {
        return Transaction::selectRaw('user_id, COUNT(*) as count')
            ->where('created_at', '>', now()->subMinutes($minutes))
            ->groupBy('user_id')
            ->having('count', '>', $threshold)
            ->with('metadata')
            ->get();
    }

    public function getVelocityByIp(string $ip, int $minutes = 5): int
    {
        return RequestMetadata::where('ip_address', $ip)
            ->where('created_at', '>', now()->subMinutes($minutes))
            ->count();
    }

    public function detectHighVelocityByIp(string $threshold = 10, int $minutes = 5): Collection
    {
        return RequestMetadata::selectRaw('ip_address, COUNT(*) as count')
            ->where('created_at', '>', now()->subMinutes($minutes))
            ->groupBy('ip_address')
            ->having('count', '>', $threshold)
            ->get();
    }
}
```

## Amount Analysis

### Detect Amount Anomalies

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Akira\Sisp\Transaction;
use Illuminate\Support\Collection;

final readonly class AmountAnomalyDetector
{
    public function getUserAverageAmount(int $userId): float
    {
        return Transaction::where('user_id', $userId)->avg('amount') ?? 0;
    }

    public function getUserMedianAmount(int $userId): float
    {
        $amounts = Transaction::where('user_id', $userId)
            ->pluck('amount')
            ->toArray();

        if (empty($amounts)) {
            return 0;
        }

        sort($amounts);
        $count = count($amounts);

        return $count % 2 === 0
            ? ($amounts[$count / 2 - 1] + $amounts[$count / 2]) / 2
            : $amounts[$count / 2];
    }

    public function getUserAmountStandardDeviation(int $userId): float
    {
        $amounts = Transaction::where('user_id', $userId)
            ->pluck('amount')
            ->toArray();

        if (empty($amounts)) {
            return 0;
        }

        $average = array_sum($amounts) / count($amounts);
        $variance = array_sum(array_map(function ($x) use ($average) {
            return pow($x - $average, 2);
        }, $amounts)) / count($amounts);

        return sqrt($variance);
    }

    public function detectAnomalousAmount(int $userId, float $amount, int $stdDeviation = 2): bool
    {
        $average = $this->getUserAverageAmount($userId);
        $stdDev = $this->getUserAmountStandardDeviation($userId);

        if ($stdDev === 0) {
            return $amount > $average * 2; // 2x average if no std dev
        }

        $threshold = $average + ($stdDeviation * $stdDev);
        return $amount > $threshold;
    }

    public function getAnomalousTransactions(int $userId, int $stdDeviation = 2): Collection
    {
        $average = $this->getUserAverageAmount($userId);
        $stdDev = $this->getUserAmountStandardDeviation($userId);

        if ($average === 0 || $stdDev === 0) {
            return Transaction::where('user_id', $userId)
                ->where('amount', '>', $average * 2)
                ->get();
        }

        $threshold = $average + ($stdDeviation * $stdDev);

        return Transaction::where('user_id', $userId)
            ->where('amount', '>', $threshold)
            ->orderByDesc('amount')
            ->get();
    }

    public function getMaximumUserAmount(int $userId): float
    {
        return Transaction::where('user_id', $userId)->max('amount') ?? 0;
    }

    public function getMinimumUserAmount(int $userId): float
    {
        return Transaction::where('user_id', $userId)->min('amount') ?? 0;
    }
}
```

## Combined Fraud Score

### Calculate Custom Fraud Score

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Akira\Sisp\Models\RequestMetadata;
use Akira\Sisp\Transaction;

final readonly class CustomFraudScoreCalculator
{
    public function __construct(
        private GeoAnomalyDetector $geoDetector,
        private DeviceChangeDetector $deviceDetector,
        private VelocityAnalyzer $velocityAnalyzer,
        private AmountAnomalyDetector $amountDetector,
    ) {}

    public function calculateScore(Transaction $transaction): int
    {
        $score = 0;

        // Metadata score (0-25)
        $metadata = RequestMetadata::where('transaction_id', $transaction->id)->first();
        if ($metadata) {
            $score += $metadata->risk_score / 4;
        }

        // Geographic anomaly (0-15)
        if ($this->geoDetector->detectAnomalies($transaction->user_id)->count() > 0) {
            $score += 15;
        }

        // Device change (0-15)
        if ($this->deviceDetector->detectDeviceChanges($transaction->user_id)->count() > 0) {
            $score += 15;

            // Extra points if high number of different devices
            $uniqueDevices = $this->deviceDetector->getUniqueFingerprintsForUser($transaction->user_id);
            if ($uniqueDevices > 5) {
                $score += 10;
            }
        }

        // Velocity check (0-20)
        if ($this->velocityAnalyzer->isHighVelocity($transaction->user_id)) {
            $score += 20;
        }

        // Amount anomaly (0-15)
        if ($this->amountDetector->detectAnomalousAmount($transaction->user_id, $transaction->amount)) {
            $score += 15;
        }

        // VPN/Proxy (0-10)
        if ($metadata && ($metadata->is_vpn || $metadata->is_proxy)) {
            $score += 10;
        }

        return min($score, 100); // Cap at 100
    }

    public function isSuspicious(Transaction $transaction, int $threshold = 60): bool
    {
        return $this->calculateScore($transaction) >= $threshold;
    }
}
```

## Reporting & Dashboards

### Generate Daily Report

```php
<?php

declare(straight_types=1);

namespace App\Services;

use Akira\Sisp\Models\RequestMetadata;
use Akira\Sisp\Models\RateLimit;
use Akira\Sisp\Transaction;
use Carbon\Carbon;

final readonly class DailyFraudReport
{
    public function generate(Carbon $date): array
    {
        $startOfDay = $date->clone()->startOfDay();
        $endOfDay = $date->clone()->endOfDay();

        return [
            'date' => $date->toDateString(),
            'total_transactions' => Transaction::whereBetween('created_at', [$startOfDay, $endOfDay])->count(),
            'total_amount' => Transaction::whereBetween('created_at', [$startOfDay, $endOfDay])->sum('amount'),
            'high_risk_count' => RequestMetadata::where('risk_score', '>', 60)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->count(),
            'vpn_usage_count' => RequestMetadata::where('is_vpn', true)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->count(),
            'proxy_usage_count' => RequestMetadata::where('is_proxy', true)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->count(),
            'rate_limit_violations' => RateLimit::where('is_blocked', true)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->count(),
            'top_countries' => RequestMetadata::selectRaw('country_code, country_name, COUNT(*) as count')
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->groupBy('country_code', 'country_name')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
                ->toArray(),
            'device_breakdown' => RequestMetadata::selectRaw('device_type, COUNT(*) as count')
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->groupBy('device_type')
                ->get()
                ->toArray(),
        ];
    }

    public function generateWeekly(Carbon $startDate): array
    {
        $reports = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->clone()->addDays($i);
            $reports[] = $this->generate($date);
        }

        return $reports;
    }
}
```

### Get Top Risky IPs

```php
<?php

$topRiskyIps = RequestMetadata::selectRaw('ip_address, COUNT(*) as count, AVG(risk_score) as avg_risk')
    ->groupBy('ip_address')
    ->orderByDesc('avg_risk')
    ->limit(20)
    ->get();

foreach ($topRiskyIps as $ip) {
    echo "{$ip->ip_address}: Risk={$ip->avg_risk}, Attempts={$ip->count}\n";
}
```

### Get Suspicious Users

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Akira\Sisp\Models\RequestMetadata;
use Illuminate\Support\Collection;

final readonly class SuspiciousUserFinder
{
    public function findSuspiciousUsers(int $threshold = 60, int $days = 30): Collection
    {
        return RequestMetadata::selectRaw('
            transaction_id,
            user_id,
            COUNT(*) as count,
            AVG(risk_score) as avg_risk,
            MAX(risk_score) as max_risk
        ')
            ->where('risk_score', '>', $threshold)
            ->where('created_at', '>', now()->subDays($days))
            ->groupBy('transaction_id', 'user_id')
            ->orderByDesc('avg_risk')
            ->get();
    }
}
```

## See Also

- [Security & Fraud Detection](./security-and-fraud-detection.md) - Comprehensive fraud detection guide
- [Middleware & Security](./middleware-and-security.md) - Middleware security setup
- [API Reference](./api-reference.md) - Complete API documentation
- [Events & Monitoring](./events-and-monitoring.md) - Monitoring and alerting
- [Rate Limiting](./rate-limiting.md) - Rate limiting system
- [Configuration Reference](./configuration.md) - Configuration options
