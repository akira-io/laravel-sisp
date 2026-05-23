<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Contracts\SispCredentialsResolver;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\SispTransactionStatusResponse;
use Illuminate\Support\Facades\Http;
use LogicException;

final readonly class QueryTransactionStatusAction
{
    public function __construct(
        private LoadConfig $config,
        private SispCredentialsResolver $resolver,
    ) {}

    public function handle(Transaction|string $transaction): SispTransactionStatusResponse
    {
        $merchantRef = $transaction instanceof Transaction
            ? (string) $transaction->getAttribute('merchant_ref')
            : $transaction;

        $portalId = $this->config->getTransactionStatusPortalId();
        $portalPassword = $this->config->getTransactionStatusPortalPassword();

        throw_if($portalId === '' || $portalPassword === '', LogicException::class, 'SISP transaction status portal credentials are not configured.');

        $credentials = $this->resolver->resolve();

        $response = Http::acceptJson()
            ->asJson()
            ->timeout($this->config->getTransactionStatusTimeoutSeconds())
            ->withBasicAuth($portalId, $portalPassword)
            ->post($this->config->getTransactionStatusUrl(), [
                'posID' => $credentials->posId,
                'posAuthCode' => $credentials->posAutCode,
                'merchantRef' => $merchantRef,
            ]);

        if (! $response->successful()) {
            return SispTransactionStatusResponse::from([
                'result' => false,
                'transactionSuccess' => false,
                'transactionStatusDescription' => '',
                'msg' => "SISP transaction status request failed with HTTP {$response->status()}.",
            ]);
        }

        return SispTransactionStatusResponse::from($response->json() ?? []);
    }
}
