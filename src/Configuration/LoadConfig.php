<?php

declare(strict_types=1);

namespace Akira\Sisp\Configuration;

use Illuminate\Contracts\Config\Repository;

final readonly class LoadConfig
{
    public function __construct(
        private Repository $config,
    ) {}

    public function shouldUseInertia(): bool
    {
        return $this->config->get('sisp.use_inertia.enabled', false) && class_exists(\Inertia\Inertia::class);
    }

    public function shouldUseBlade(): bool
    {
        return $this->config->get('sisp.use_blade.enabled', true);
    }

    public function getPaymentFormComponent(): string
    {
        return $this->config->get('sisp.use_inertia.payment_form_component', 'sisp/payment-form');
    }

    public function getPaymentResponseComponent(): string
    {
        return $this->config->get('sisp.use_inertia.payment_response_component', 'sisp/payment-response');
    }

    public function getPaymentFormView(): string
    {
        return $this->config->get('sisp.use_blade.payment_form', 'sisp::payment-form');
    }

    public function getPaymentResponseView(): string
    {
        return $this->config->get('sisp.use_blade.payment_response', 'sisp::payment-response');
    }

    public function isSandboxEnabled(): bool
    {
        return $this->config->get('sisp.sandbox', false);
    }

    public function getMerchantReference(): string
    {
        $configured = $this->config->get('sisp.merchant_ref');

        return $configured ?? 'R'.date('YmdHis');
    }

    public function getMerchantSession(): string
    {
        $configured = $this->config->get('sisp.merchant_session');

        return $configured ?? 'S'.date('YmdHis');
    }

    public function getTimeStamp(): string
    {
        return date('Y-m-d H:i:s');
    }

    public function getCurrency(): string
    {
        return $this->config->get('sisp.currency', '132');
    }

    public function getMerchantId(): string
    {
        return $this->config->get('sisp.merchantId', '');
    }

    public function getPosId(): string
    {
        return $this->config->get('sisp.posID', '');
    }

    public function getPosAutCode(): string
    {
        return $this->config->get('sisp.posAutCode', '');
    }

    public function getIs3Dsec(): string
    {
        return $this->config->get('sisp.is_3dsec', '0');
    }

    public function getUrlMerchantResponse(): string
    {
        $configured = $this->config->get('sisp.url_merchant_response');

        return $configured ?? route('sisp.callback');
    }

    public function getLanguageMessages(): string
    {
        return $this->config->get('sisp.language_messages', 'EN');
    }

    public function getFingerprintVersion(): string
    {
        return $this->config->get('sisp.fingerprint_version', '1');
    }

    public function getDefaultTransactionCode(): string
    {
        return $this->config->get('sisp.transaction_code', '1');
    }

    public function getUri(): string
    {
        return $this->config->get('sisp.url', '');
    }

    public function getInvoiceNumberFormat(): string
    {
        return $this->config->get('sisp.invoice.number_format', 'date-based');
    }

    public function getInvoiceNumberPrefix(): string
    {
        return $this->config->get('sisp.invoice.prefix', 'INV');
    }

    public function getInvoiceStorageDisk(): string
    {
        return $this->config->get('sisp.invoice.disk', 'public');
    }

    public function getInvoiceTemplate(): string
    {
        return $this->config->get('sisp.invoice.template', 'branded');
    }

    public function getInvoiceCompanyName(): string
    {
        return $this->config->get('sisp.invoice.company_name', '');
    }

    public function getInvoiceCompanyAddress(): string
    {
        return $this->config->get('sisp.invoice.company_address', '');
    }

    public function getInvoiceCompanyCode(): string
    {
        return $this->config->get('sisp.invoice.company_code', '');
    }

    public function getInvoiceCompanyCountry(): string
    {
        return $this->config->get('sisp.invoice.company_country', '');
    }

    public function getInvoiceCompanyPhone(): string
    {
        return $this->config->get('sisp.invoice.company_phone', '');
    }

    public function getInvoiceCompanyEmail(): string
    {
        return $this->config->get('sisp.invoice.company_email', '');
    }

    public function getInvoiceCompanyWebsite(): string
    {
        return $this->config->get('sisp.invoice.company_website', '');
    }

    public function isRateLimitingEnabled(): bool
    {
        return $this->config->get('sisp.rate_limiting.enabled', true);
    }

    public function isMetadataCollectionEnabled(): bool
    {
        return $this->config->get('sisp.security.collect_metadata', true);
    }

    public function shouldBlockVpnProxy(): bool
    {
        return $this->config->get('sisp.security.block_vpn_proxy', true);
    }

    public function shouldBlockNewCountryPayments(): bool
    {
        return $this->config->get('sisp.security.block_new_country_payments', false);
    }

    public function isVpnDetectionEnabled(): bool
    {
        return $this->config->get('sisp.security.detect_vpn', true);
    }

    public function isProxyDetectionEnabled(): bool
    {
        return $this->config->get('sisp.security.detect_proxy', true);
    }

    public function isRiskScoringEnabled(): bool
    {
        return $this->config->get('sisp.security.calculate_risk_score', true);
    }

    public function getRateLimitPerIp(): int
    {
        return (int) $this->config->get('sisp.rate_limiting.per_ip.limit', 100);
    }

    public function getRateLimitWindowSeconds(): int
    {
        return (int) $this->config->get('sisp.rate_limiting.per_ip.window_seconds', 3600);
    }

    public function getGeolocationProvider(): string
    {
        return $this->config->get('sisp.geolocation.provider', 'maxmind');
    }

    public function isRetryAllowed(): bool
    {
        return $this->config->get('sisp.allow_retry', true);
    }

    public function getInvoiceTemporaryUrlExpirationHours(): int
    {
        return (int) $this->config->get('sisp.invoice.temporary_url_expiration_hours', 24);
    }
}
