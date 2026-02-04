<?php

declare(strict_types=1);

namespace Akira\Sisp;

use Akira\Sisp\Actions\BuildRequestPayloadAction;
use Akira\Sisp\Actions\BuildSandboxPayloadAction;
use Akira\Sisp\Actions\CreateTransactionAction;
use Akira\Sisp\Actions\HandleCallbackAction;
use Akira\Sisp\Actions\ValidatePaymentResponseFingerprintAction;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Configuration\ScopedSispCredentialsResolver;
use Akira\Sisp\Contracts\SispCredentialsResolver;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Akira\Sisp\ValueObjects\SispCredentials;
use Akira\Sisp\ValueObjects\TransactionData;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Collection;

final readonly class ScopedSisp
{
    private SispCredentialsResolver $resolver;

    public function __construct(
        private LoadConfig $loadConfig,
        private Container $container,
        SispCredentials $credentials,
    ) {
        $this->resolver = new ScopedSispCredentialsResolver($credentials);
    }

    public function getTransactions(): \Illuminate\Database\Eloquent\Builder
    {
        return Transaction::query();
    }

    public function buildRequestPayload(PaymentRequestData $data): PaymentRequest
    {
        return $this->withResolver(fn (): PaymentRequest => $this->container->make(BuildRequestPayloadAction::class)->handle($data));
    }

    public function validateCallback(CallbackPayload $payload): bool
    {
        return $this->withResolver(fn (): bool => $this->container->make(ValidatePaymentResponseFingerprintAction::class)->handle($payload));
    }

    public function handlePaymentCallback(CallbackPayload $payload): Transaction
    {
        return $this->withResolver(fn (): Transaction => $this->container->make(HandleCallbackAction::class)->handle($payload));
    }

    public function generateSandboxPayload(PaymentRequestData $data, string $status = 'success'): CallbackPayload
    {
        return $this->withResolver(fn (): CallbackPayload => $this->container->make(BuildSandboxPayloadAction::class)->handle($data, $status));
    }

    public function storeTransaction(TransactionData $data): Transaction
    {
        return $this->withResolver(fn (): Transaction => $this->container->make(CreateTransactionAction::class)->handle($data));
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
        return $this->resolver->resolve()->currency;
    }

    public function getPosId(): string
    {
        return $this->resolver->resolve()->posId;
    }

    public function getPosAutCode(): string
    {
        return $this->resolver->resolve()->posAutCode;
    }

    public function getIs3Dsec(): string
    {
        return $this->resolver->resolve()->is3DSec;
    }

    public function getUrlMerchantResponse(): string
    {
        return $this->resolver->resolve()->urlMerchantResponse ?? route('sisp.callback');
    }

    public function getLanguageMessages(): string
    {
        return $this->resolver->resolve()->languageMessages;
    }

    public function getFingerprintVersion(): string
    {
        return $this->resolver->resolve()->fingerprintVersion;
    }

    public function getDefaultTransactionCode(): string
    {
        return $this->loadConfig->getDefaultTransactionCode();
    }

    public function getUri(): string
    {
        return $this->resolver->resolve()->url;
    }

    private function withResolver(callable $callback): mixed
    {
        $original = $this->container->make(SispCredentialsResolver::class);
        $this->container->instance(SispCredentialsResolver::class, $this->resolver);

        try {
            return $callback();
        } finally {
            $this->container->instance(SispCredentialsResolver::class, $original);
        }
    }
}
