<?php

declare(strict_types=1);

namespace Akira\Sisp\Drivers;

use Akira\Sisp\Contracts\SispCredentialsResolver;
use Akira\Sisp\Contracts\SispDriver;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\TransactionStatusResponse;
use Illuminate\Contracts\Container\Container;

final readonly class ProductionDriver implements SispDriver
{
    public function __construct(
        private Container $container,
        private TransactionStatusClient $statusClient,
    ) {}

    public function name(): string
    {
        return 'production';
    }

    public function paymentEndpoint(): string
    {
        // Resolved per call: the manager caches this driver, and the active
        // credentials change under Sisp::forCredentials().
        return $this->container->make(SispCredentialsResolver::class)->resolve()->url;
    }

    public function queryTransactionStatus(Transaction|string $transaction): TransactionStatusResponse
    {
        return $this->statusClient->query($transaction);
    }
}
