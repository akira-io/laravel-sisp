<?php

declare(strict_types=1);

namespace Akira\Sisp\Configuration;

use Akira\Sisp\Configuration\Concerns\LoadsDocumentConfig;
use Akira\Sisp\Pipelines\Callback\HandleCallbackPipeline;
use Akira\Sisp\Pipelines\Payment\ProcessPaymentPipeline;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Config\Repository;

#[Singleton]
final readonly class LoadConfig
{
    use LoadsDocumentConfig;

    public function __construct(
        private Repository $config,
    ) {}

    public function shouldUseInertia(): bool
    {
        return $this->boolean('sisp.use_inertia.enabled', false) && class_exists(\Inertia\Inertia::class);
    }

    public function shouldUseBlade(): bool
    {
        return $this->boolean('sisp.use_blade.enabled', true);
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
        return $this->boolean('sisp.sandbox', false);
    }

    /**
     * @return array<int, class-string>
     */
    public function getPaymentPipes(): array
    {
        return $this->config->get('sisp.pipelines.payment', ProcessPaymentPipeline::DEFAULT_PIPES);
    }

    /**
     * @return array<int, class-string>
     */
    public function getCallbackPipes(): array
    {
        return $this->config->get('sisp.pipelines.callback', HandleCallbackPipeline::DEFAULT_PIPES);
    }

    public function getMerchantReference(): string
    {
        return resolve($this->config->get('sisp.generators.merchantReference'))();
    }

    public function getMerchantSession(): string
    {
        return resolve($this->config->get('sisp.generators.merchantSession'))();
    }

    public function getTimeStamp(): string
    {
        return resolve($this->config->get('sisp.generators.timeStamp'))();
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
        return $this->config->get('sisp.url_merchant_response') ?? route('sisp.callback');
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

    public function isRateLimitingEnabled(): bool
    {
        return $this->boolean('sisp.rate_limiting.enabled', true);
    }

    public function isIdempotencyEnabled(): bool
    {
        return $this->boolean('sisp.idempotency.enabled', true);
    }

    /** @return list<string> */
    public function getIdempotencyRequestKeys(): array
    {
        $keys = $this->config->get('sisp.idempotency.request_keys', ['idempotency_key', 'checkout_intent_id']);

        if (! is_array($keys)) {
            return ['idempotency_key', 'checkout_intent_id'];
        }

        return array_values(array_filter(
            $keys,
            fn (mixed $key): bool => is_string($key) && $key !== '',
        ));
    }

    public function isMetadataCollectionEnabled(): bool
    {
        return $this->boolean('sisp.security.collect_metadata', true);
    }

    public function shouldBlockVpnProxy(): bool
    {
        return $this->boolean('sisp.security.block_vpn_proxy', false);
    }

    public function shouldBlockNewCountryPayments(): bool
    {
        return $this->boolean('sisp.security.block_new_country_payments', false);
    }

    public function isVpnDetectionEnabled(): bool
    {
        return $this->boolean('sisp.security.detect_vpn', false);
    }

    public function isProxyDetectionEnabled(): bool
    {
        return $this->boolean('sisp.security.detect_proxy', false);
    }

    public function isRiskScoringEnabled(): bool
    {
        return $this->boolean('sisp.security.calculate_risk_score', false);
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
        return $this->boolean('sisp.allow_retry', true);
    }

    public function getIdentifierGenerationMaxAttempts(): int
    {
        return max(1, (int) $this->config->get('sisp.identifier_generation.max_attempts', 5));
    }

    public function getIdentifierGenerationCollisionRetrySleepMicroseconds(): int
    {
        return max(0, (int) $this->config->get('sisp.identifier_generation.collision_retry_sleep_microseconds', 1000000));
    }

    public function getInvoiceTemporaryUrlExpirationHours(): int
    {
        return (int) $this->config->get('sisp.invoice.temporary_url_expiration_hours', 24);
    }

    private function boolean(string $key, bool $default): bool
    {
        return filter_var($this->config->get($key, $default), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
