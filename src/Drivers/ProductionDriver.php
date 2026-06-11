<?php

declare(strict_types=1);

namespace Akira\Sisp\Drivers;

use Akira\Sisp\Contracts\SispCredentialsResolver;
use Akira\Sisp\Contracts\SispDriver;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\TransactionStatusResponse;

final readonly class ProductionDriver implements SispDriver
{
    public function __construct(
        private SispCredentialsResolver $credentialsResolver,
        private TransactionStatusClient $statusClient,
    ) {}

    public function name(): string
    {
        return 'production';
    }

    public function paymentEndpoint(): string
    {
        return $this->credentialsResolver->resolve()->url;
    }

    public function queryTransactionStatus(Transaction|string $transaction): TransactionStatusResponse
    {
        return $this->statusClient->query($transaction);
    }
}
