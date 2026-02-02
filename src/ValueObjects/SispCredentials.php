<?php

declare(strict_types=1);

namespace Akira\Sisp\ValueObjects;

use Akira\Sisp\Configuration\LoadConfig;

final readonly class SispCredentials
{
    public function __construct(
        public string $posId,
        public string $posAutCode,
        public string $currency,
        public string $merchantId,
        public string $url,
        public string $languageMessages,
        public string $fingerprintVersion,
        public string $is3DSec,
        public bool $sandbox,
        public ?string $urlMerchantResponse = null,
    ) {}

    public static function fromConfig(LoadConfig $config): self
    {
        return new self(
            posId: $config->getPosId(),
            posAutCode: $config->getPosAutCode(),
            currency: $config->getCurrency(),
            merchantId: $config->getMerchantId(),
            url: $config->getUri(),
            languageMessages: $config->getLanguageMessages(),
            fingerprintVersion: $config->getFingerprintVersion(),
            is3DSec: $config->getIs3Dsec(),
            sandbox: $config->isSandboxEnabled(),
            urlMerchantResponse: $config->getUrlMerchantResponse(),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function from(array $data): self
    {
        return new self(
            posId: $data['pos_id'] ?? $data['posId'] ?? '',
            posAutCode: $data['pos_aut_code'] ?? $data['posAutCode'] ?? '',
            currency: $data['currency'] ?? '132',
            merchantId: $data['merchant_id'] ?? $data['merchantId'] ?? '',
            url: $data['url'] ?? '',
            languageMessages: $data['language_messages'] ?? $data['languageMessages'] ?? 'EN',
            fingerprintVersion: $data['fingerprint_version'] ?? $data['fingerprintVersion'] ?? '1',
            is3DSec: $data['is_3d_sec'] ?? $data['is3DSec'] ?? '0',
            sandbox: $data['sandbox'] ?? false,
            urlMerchantResponse: $data['url_merchant_response'] ?? $data['urlMerchantResponse'] ?? null,
        );
    }
}
