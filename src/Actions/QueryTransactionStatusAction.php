<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Drivers\SispManager;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\TransactionStatusResponse;

final readonly class QueryTransactionStatusAction
{
    public function __construct(private SispManager $manager) {}

    public function handle(Transaction|string $transaction): TransactionStatusResponse
    {
        return $this->manager->driver()->queryTransactionStatus($transaction);
    }
}
