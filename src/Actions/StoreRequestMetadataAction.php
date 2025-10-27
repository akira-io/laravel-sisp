<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Models\RequestMetadata;
use Akira\Sisp\Transaction;
use Illuminate\Http\Request;

final readonly class StoreRequestMetadataAction
{
    public function handle(Request $request, Transaction $transaction = null): RequestMetadata
    {
        $fingerprint = $this->generateDeviceFingerprint($request);
        $ipInfo = $this->parseIpAddress($request);

        return RequestMetadata::create([
            'transaction_id' => $transaction?->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'country_code' => $ipInfo['country_code'] ?? null,
            'country_name' => $ipInfo['country_name'] ?? null,
            'region' => $ipInfo['region'] ?? null,
            'city' => $ipInfo['city'] ?? null,
            'latitude' => $ipInfo['latitude'] ?? null,
            'longitude' => $ipInfo['longitude'] ?? null,
            'isp' => $ipInfo['isp'] ?? null,
            'device_type' => $this->detectDeviceType($request),
            'browser' => $this->detectBrowser($request),
            'os' => $this->detectOS($request),
            'device_fingerprint' => $fingerprint,
            'is_vpn' => $ipInfo['is_vpn'] ?? false,
            'is_proxy' => $ipInfo['is_proxy'] ?? false,
            'is_mobile' => $this->isMobileDevice($request),
        ]);
    }

    private function generateDeviceFingerprint(Request $request): string
    {
        $components = [
            $request->ip(),
            $request->userAgent(),
            $request->header('accept-language'),
            $request->header('accept-encoding'),
        ];

        return hash('sha256', implode('|', $components));
    }

    private function parseIpAddress(Request $request): array
    {
        $ip = $request->ip();

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return [];
        }

        return [
            'country_code' => geoip()->getCountryCode($ip),
            'country_name' => geoip()->getCountryName($ip),
            'region' => geoip()->getState($ip),
            'city' => geoip()->getCity($ip),
            'latitude' => geoip()->getLatitude($ip),
            'longitude' => geoip()->getLongitude($ip),
            'isp' => geoip()->getISP($ip),
        ];
    }

    private function detectDeviceType(Request $request): string
    {
        $userAgent = strtolower($request->userAgent() ?? '');

        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android')) {
            return 'mobile';
        }

        if (str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')) {
            return 'tablet';
        }

        return 'desktop';
    }

    private function detectBrowser(Request $request): string
    {
        $userAgent = $request->userAgent() ?? '';

        if (preg_match('/Chrome/', $userAgent)) {
            return 'Chrome';
        }
        if (preg_match('/Firefox/', $userAgent)) {
            return 'Firefox';
        }
        if (preg_match('/Safari/', $userAgent) && !preg_match('/Chrome/', $userAgent)) {
            return 'Safari';
        }
        if (preg_match('/MSIE|Trident/', $userAgent)) {
            return 'IE';
        }
        if (preg_match('/Edge/', $userAgent)) {
            return 'Edge';
        }

        return 'Unknown';
    }

    private function detectOS(Request $request): string
    {
        $userAgent = $request->userAgent() ?? '';

        if (preg_match('/Windows/', $userAgent)) {
            return 'Windows';
        }
        if (preg_match('/Macintosh|Mac OS/', $userAgent)) {
            return 'macOS';
        }
        if (preg_match('/Linux/', $userAgent)) {
            return 'Linux';
        }
        if (preg_match('/Android/', $userAgent)) {
            return 'Android';
        }
        if (preg_match('/iOS|iPhone|iPad/', $userAgent)) {
            return 'iOS';
        }

        return 'Unknown';
    }

    private function isMobileDevice(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');

        return str_contains($userAgent, 'mobile') ||
            str_contains($userAgent, 'android') ||
            str_contains($userAgent, 'iphone') ||
            str_contains($userAgent, 'ipad');
    }
}
