<?php

declare(strict_types=1);

namespace Akira\Sisp\Drivers;

use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Contracts\SispCredentialsResolver;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\TransactionStatusResponse;
use Illuminate\Support\Facades\Http;
use LogicException;

final readonly class TransactionStatusClient
{
    public function __construct(
        private LoadConfig $config,
        private SispCredentialsResolver $credentialsResolver,
    ) {}

    public function query(Transaction|string $transaction): TransactionStatusResponse
    {
        $merchantRef = $transaction instanceof Transaction
            ? (string) $transaction->getAttribute('merchant_ref')
            : $transaction;

        $portalId = $this->config->getTransactionStatusPortalId();
        $portalPassword = $this->config->getTransactionStatusPortalPassword();

        throw_if($portalId === '' || $portalPassword === '', LogicException::class, 'SISP transaction status portal credentials are not configured.');

        $credentials = $this->credentialsResolver->resolve();

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
            return TransactionStatusResponse::from([
                'result' => false,
                'transactionSuccess' => false,
                'transactionStatusDescription' => '',
                'msg' => "SISP transaction status request failed with HTTP {$response->status()}.",
            ]);
        }

        return TransactionStatusResponse::from($response->json() ?? []);
    }
}
