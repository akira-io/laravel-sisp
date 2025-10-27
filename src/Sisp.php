<?php

declare(strict_types=1);

namespace Akira\Sisp;

use Akira\Sisp\Actions\BuildRequestPayloadAction;
use Akira\Sisp\Actions\BuildSandboxPayloadAction;
use Akira\Sisp\Actions\CreateTransactionAction;
use Akira\Sisp\Actions\HandleCallbackAction;
use Akira\Sisp\Actions\ValidateFingerprintAction;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Akira\Sisp\ValueObjects\TransactionData;
use Illuminate\Database\Eloquent\Collection;

final class Sisp
{
    public function __construct(
        private BuildRequestPayloadAction $buildRequestPayload,
        private BuildSandboxPayloadAction $buildSandboxPayload,
        private ValidateFingerprintAction $validateFingerprint,
        private CreateTransactionAction $createTransaction,
        private HandleCallbackAction $handleCallback,
        private LoadConfig $loadConfig,
    ) {}

    public function getTransactions(): Collection
    {
        return Transaction::get();
    }

    public function buildRequestPayload(PaymentRequestData $data): PaymentRequest
    {
        return $this->buildRequestPayload->handle($data);
    }

    public function validateCallback(array $payload): bool
    {
        $callbackPayload = CallbackPayload::from($payload);
        $fingerprint = $callbackPayload->fingerprint;

        return $this->validateFingerprint->handle($callbackPayload->withoutFingerprint(), $fingerprint);
    }

    public function handlePaymentCallback(array $payload): Transaction
    {
        return $this->handleCallback->handle($payload);
    }

    public function generateSandboxPayload(PaymentRequestData $data, string $status = 'success'): CallbackPayload
    {
        return $this->buildSandboxPayload->handle($data, $status);
    }

    public function createTransaction(TransactionData $data): Transaction
    {
        return $this->createTransaction->handle($data);
    }

    public function getMerchantReference(): string
    {
        return $this->loadConfig->getMerchantReference();
    }

    public function getMerchantSession(): string
    {
        return $this->loadConfig->getMerchantSession();
    }

    public function getTimeStamp(): string
    {
        return $this->loadConfig->getTimeStamp();
    }

    public function getCurrency(): string
    {
        return $this->loadConfig->getCurrency();
    }

    public function getPosId(): string
    {
        return $this->loadConfig->getPosId();
    }

    public function getPosAutCode(): string
    {
        return $this->loadConfig->getPosAutCode();
    }

    public function getIs3Dsec(): string
    {
        return $this->loadConfig->getIs3Dsec();
    }

    public function getUrlMerchantResponse(): string
    {
        return $this->loadConfig->getUrlMerchantResponse();
    }

    public function getLanguageMessages(): string
    {
        return $this->loadConfig->getLanguageMessages();
    }

    public function getFingerprintVersion(): string
    {
        return $this->loadConfig->getFingerprintVersion();
    }

    public function getDefaultTransactionCode(): string
    {
        return $this->loadConfig->getDefaultTransactionCode();
    }
    public function getUri(): string
    {
        return $this->loadConfig->getUri();
    }
}
