<?php

declare(strict_types=1);

namespace Akira\Sisp\Concerns;

use RuntimeException;

trait Configurations
{
    /**
     * Get and validate the SISP base URL from config.
     */
    public function getUri(): string
    {
        return $this->requireConfig('sisp.url', 'SISP URL');
    }

    /**
     * Get and validate the POS ID from config.
     *
     * @throws RuntimeException
     */
    public function getPosId(): string
    {
        return $this->requireConfig('sisp.posID', 'POS ID');
    }

    /**
     * Get and validate the POS Authorization Code from config.
     *
     * @throws RuntimeException
     */
    public function getPosAutCode(): string
    {
        return $this->requireConfig('sisp.posAutCode', 'POS Authorization Code');
    }

    /**
     * Get and validate the Merchant ID from config.
     *
     * @throws RuntimeException
     */
    public function getMerchantId(): string
    {
        return $this->requireConfig('sisp.merchantId', 'Merchant ID');
    }

    /**
     * Get and validate the default transaction code from config.
     *
     * @throws RuntimeException
     */
    public function getDefaultTransactionCode(): string
    {
        return $this->requireConfig('sisp.transactionCode', 'Transaction Code');
    }

    /**
     * Get and validate the currency value from config.
     *
     * @throws RuntimeException
     */
    public function getCurrency(): string
    {
        return $this->requireConfig('sisp.currency', 'Currency');
    }

    /**
     * Get and validate the 3D Secure setting from config.
     *
     * @throws RuntimeException
     */
    public function getIs3Dsec(): string
    {
        return $this->requireConfig('sisp.is3DSec', '3D Secure (is3DSec)');
    }

    /**
     * Get and validate the merchant response URL from config.
     *
     * @throws RuntimeException
     */
    public function getUrlMerchantResponse(): string
    {
        return $this->requireConfig('sisp.urlMerchantResponse', 'Merchant Response URL');
    }

    /**
     * Get and validate the language messages setting from config.
     *
     * @throws RuntimeException
     */
    public function getLanguageMessages(): string
    {
        return $this->requireConfig('sisp.languageMessages', 'Language Messages');
    }

    /**
     * Get and validate the fingerprint version from config.
     *
     * @throws RuntimeException
     */
    public function getFingerprintVersion(): string
    {
        return $this->requireConfig('sisp.fingerPrintVersion', 'Fingerprint Version');
    }

    /**
     * Resolve and invoke the configured Merchant Reference generator.
     *
     * @throws RuntimeException
     */
    public function getMerchantReference(): mixed
    {
        return $this->resolveGenerator('sisp.generators.merchantReference', 'Merchant Reference');
    }

    /**
     * Resolve and invoke the configured Merchant Session generator.
     *
     * @throws RuntimeException
     */
    public function getMerchantSession(): mixed
    {
        return $this->resolveGenerator('sisp.generators.merchantSession', 'Merchant Session');
    }

    /**
     * Resolve and invoke the configured Timestamp generator.
     *
     * @throws RuntimeException
     */
    public function getTimeStamp(): mixed
    {
        return $this->resolveGenerator('sisp.generators.timeStamp', 'Timestamp');
    }

    /**
     * Ensure a config value is set and cast it to string.
     *
     * @throws RuntimeException
     */
    protected function requireConfig(string $key, string $label): string
    {
        $value = config($key);

        if ($value === null) {
            throw new RuntimeException("{$label} is not configured [{$key}].");
        }

        return type($value)->asString();
    }

    /**
     * Resolve a configured class or closure and invoke it.
     *
     * @throws RuntimeException
     */
    protected function resolveGenerator(string $key, string $label): mixed
    {
        $generator = type(config($key))->asString();

        if ($generator === '' || $generator === '0') {
            throw new RuntimeException("Generator for {$label} is not configured [{$key}].");
        }

        return resolve($generator)();
    }
}
