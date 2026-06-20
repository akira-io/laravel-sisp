<?php

declare(strict_types=1);

use Akira\Sisp\Configuration\LoadConfig;

beforeEach(function (): void {
    $this->cfg = resolve(LoadConfig::class);
});

it('returns sane defaults and configured values for getters', function (): void {
    expect($this->cfg->getMerchantReference())->toMatch('/^R\d{14}$/')
        ->and($this->cfg->getMerchantSession())->toMatch('/^S\d{14}$/')
        ->and($this->cfg->getTimeStamp())->toMatch('/^\d{4}-\d{2}-\d{2} /');

    config()->set('sisp.currency', 'XYZ');
    config()->set('sisp.language_messages', 'PT');
    config()->set('sisp.fingerprint_version', '9');
    config()->set('sisp.transaction_code', '42');
    config()->set('sisp.url', 'https://example.test');

    expect($this->cfg->getCurrency())->toBe('XYZ')
        ->and($this->cfg->getLanguageMessages())->toBe('PT')
        ->and($this->cfg->getFingerprintVersion())->toBe('9')
        ->and($this->cfg->getDefaultTransactionCode())->toBe('42')
        ->and($this->cfg->getUri())->toBe('https://example.test');

    config()->set('sisp.invoice', [
        'number_format' => 'date-based',
        'prefix' => 'INVX',
        'disk' => 'local',
        'template' => 'branded',
        'company_name' => 'Acme',
        'company_address' => 'Street 1',
        'company_code' => 'C-01',
        'company_country' => 'AO',
        'company_phone' => '+244...',
        'company_email' => 'mail@example.test',
        'company_website' => 'https://acme.test',
        'temporary_url_expiration_hours' => 12,
    ]);

    expect($this->cfg->getInvoiceNumberFormat())->toBe('date-based')
        ->and($this->cfg->getInvoiceNumberPrefix())->toBe('INVX')
        ->and($this->cfg->getInvoiceStorageDisk())->toBe('local')
        ->and($this->cfg->getInvoiceTemplate())->toBe('branded')
        ->and($this->cfg->getInvoiceCompanyName())->toBe('Acme')
        ->and($this->cfg->getInvoiceCompanyAddress())->toBe('Street 1')
        ->and($this->cfg->getInvoiceCompanyCode())->toBe('C-01')
        ->and($this->cfg->getInvoiceCompanyCountry())->toBe('AO')
        ->and($this->cfg->getInvoiceCompanyPhone())->toBe('+244...')
        ->and($this->cfg->getInvoiceCompanyEmail())->toBe('mail@example.test')
        ->and($this->cfg->getInvoiceCompanyWebsite())->toBe('https://acme.test')
        ->and($this->cfg->getInvoiceTemporaryUrlExpirationHours())->toBe(12);
});

it('reads boolean flags for features and security', function (): void {
    config()->set('sisp.use_blade.enabled', false);
    config()->set('sisp.sandbox', true);
    config()->set('sisp.rate_limiting.enabled', false);
    config()->set('sisp.idempotency.enabled', false);
    config()->set('sisp.security.collect_metadata', false);
    config()->set('sisp.security.block_vpn_proxy', false);
    config()->set('sisp.security.block_new_country_payments', true);
    config()->set('sisp.security.detect_vpn', false);
    config()->set('sisp.security.detect_proxy', false);
    config()->set('sisp.security.calculate_risk_score', false);
    config()->set('sisp.rate_limiting.per_ip.limit', 5);
    config()->set('sisp.rate_limiting.per_ip.window_seconds', 60);
    config()->set('sisp.geolocation.provider', 'ip2location');

    expect($this->cfg->shouldUseBlade())->toBeFalse()
        ->and($this->cfg->isSandboxEnabled())->toBeTrue()
        ->and($this->cfg->isRateLimitingEnabled())->toBeFalse()
        ->and($this->cfg->isIdempotencyEnabled())->toBeFalse()
        ->and($this->cfg->isMetadataCollectionEnabled())->toBeFalse()
        ->and($this->cfg->shouldBlockVpnProxy())->toBeFalse()
        ->and($this->cfg->shouldBlockNewCountryPayments())->toBeTrue()
        ->and($this->cfg->isVpnDetectionEnabled())->toBeFalse()
        ->and($this->cfg->isProxyDetectionEnabled())->toBeFalse()
        ->and($this->cfg->isRiskScoringEnabled())->toBeFalse()
        ->and($this->cfg->getRateLimitPerIp())->toBe(5)
        ->and($this->cfg->getRateLimitWindowSeconds())->toBe(60)
        ->and($this->cfg->getGeolocationProvider())->toBe('ip2location');
});

it('defaults unsupported advanced security controls to disabled', function (): void {
    expect($this->cfg->isIdempotencyEnabled())->toBeTrue()
        ->and($this->cfg->isMetadataCollectionEnabled())->toBeTrue()
        ->and($this->cfg->isVpnDetectionEnabled())->toBeFalse()
        ->and($this->cfg->isProxyDetectionEnabled())->toBeFalse()
        ->and($this->cfg->isRiskScoringEnabled())->toBeFalse()
        ->and($this->cfg->shouldBlockVpnProxy())->toBeFalse()
        ->and($this->cfg->shouldBlockNewCountryPayments())->toBeFalse();
});

it('uses configured payment value generators', function (): void {
    app()->singleton('sisp.test.merchantReference', fn (): object => new class
    {
        private int $next = 0;

        public function __invoke(): string
        {
            $this->next++;

            return 'CUSTOM-REF-'.$this->next;
        }
    });

    app()->singleton('sisp.test.merchantSession', fn (): object => new class
    {
        private int $next = 0;

        public function __invoke(): string
        {
            $this->next++;

            return 'CUSTOM-SESSION-'.$this->next;
        }
    });

    config()->set('sisp.generators.merchantReference', 'sisp.test.merchantReference');
    config()->set('sisp.generators.merchantSession', 'sisp.test.merchantSession');
    app()->bind('sisp.test.timeStamp', fn (): object => new class
    {
        public function __invoke(): string
        {
            return '2030-01-02 03:04:05';
        }
    });
    config()->set('sisp.generators.timeStamp', 'sisp.test.timeStamp');

    $merchantReferences = array_map(fn (): string => $this->cfg->getMerchantReference(), range(1, 3));
    $merchantSessions = array_map(fn (): string => $this->cfg->getMerchantSession(), range(1, 3));

    expect($merchantReferences)->toBe(['CUSTOM-REF-1', 'CUSTOM-REF-2', 'CUSTOM-REF-3'])
        ->and($merchantSessions)->toBe(['CUSTOM-SESSION-1', 'CUSTOM-SESSION-2', 'CUSTOM-SESSION-3'])
        ->and($this->cfg->getTimeStamp())->toBe('2030-01-02 03:04:05');
});
