<?php

declare(strict_types=1);

namespace Akira\Sisp\Drivers;

use Akira\Sisp\Contracts\SispDriver;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\TransactionStatusResponse;

final readonly class SandboxDriver implements SispDriver
{
    public function __construct(
        private TransactionStatusClient $statusClient,
    ) {}

    public function name(): string
    {
        return 'sandbox';
    }

    public function paymentEndpoint(): string
    {
        return route('sisp.sandbox');
    }

    public function queryTransactionStatus(Transaction|string $transaction): TransactionStatusResponse
    {
        return $this->statusClient->query($transaction);
    }
}
