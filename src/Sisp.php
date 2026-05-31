<?php

declare(strict_types=1);

namespace Akira\Sisp;

use Akira\Sisp\Actions\BuildRequestPayloadAction;
use Akira\Sisp\Actions\BuildSandboxPayloadAction;
use Akira\Sisp\Actions\CreateTransactionAction;
use Akira\Sisp\Actions\HandleCallbackAction;
use Akira\Sisp\Actions\QueryTransactionStatusAction;
use Akira\Sisp\Actions\ReconcileTransactionStatusAction;
use Akira\Sisp\Actions\ValidatePaymentResponseFingerprintAction;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\Countries;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Akira\Sisp\ValueObjects\SispCredentials;
use Akira\Sisp\ValueObjects\TransactionData;
use Akira\Sisp\ValueObjects\TransactionStatusResponse;

final readonly class Sisp
{
    public function __construct(
        private BuildRequestPayloadAction $buildRequestPayload,
        private BuildSandboxPayloadAction $buildSandboxPayload,
        private ValidatePaymentResponseFingerprintAction $validateFingerprint,
        private CreateTransactionAction $createTransaction,
        private HandleCallbackAction $handleCallback,
        private QueryTransactionStatusAction $queryTransactionStatus,
        private ReconcileTransactionStatusAction $reconcileTransactionStatus,
        private LoadConfig $loadConfig,
    ) {}

    public function forCredentials(SispCredentials $credentials): ScopedSisp
    {
        return new ScopedSisp(
            loadConfig: $this->loadConfig,
            container: app(),
            credentials: $credentials,
        );
    }

    public function getTransactions(): \Illuminate\Database\Eloquent\Builder
    {
        return Transaction::query();
    }

    public function buildRequestPayload(PaymentRequestData $data): PaymentRequest
    {
        return $this->buildRequestPayload->handle($data);
    }

    public function validateCallback(CallbackPayload $payload): bool
    {
        return $this->validateFingerprint->handle($payload);
    }

    public function handlePaymentCallback(CallbackPayload $payload): Transaction
    {
        return $this->handleCallback->handle($payload);
    }

    public function queryTransactionStatus(Transaction|string $transaction): TransactionStatusResponse
    {
        return $this->queryTransactionStatus->handle($transaction);
    }

    public function reconcileTransactionStatus(Transaction $transaction): Transaction
    {
        return $this->reconcileTransactionStatus->handle($transaction);
    }

    public function generateSandboxPayload(PaymentRequestData $data, string $status = 'success'): CallbackPayload
    {
        return $this->buildSandboxPayload->handle($data, $status);
    }

    public function storeTransaction(TransactionData $data): Transaction
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

    /**
     * @return array<string, array{alpha2: string, numeric: string, name: string, flag: string}>
     */
    public function countries(): array
    {
        return Countries::all();
    }

    public function getCountryNumericCode(string $alpha2): string
    {
        return Countries::getNumericCode($alpha2);
    }

    public function getCountryFlag(string $alpha2): string
    {
        return Countries::getFlag($alpha2);
    }

    public function getCountryName(string $alpha2): ?string
    {
        return Countries::getName($alpha2);
    }
}
