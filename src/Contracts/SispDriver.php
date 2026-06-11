<?php

declare(strict_types=1);

namespace Akira\Sisp\Contracts;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\TransactionStatusResponse;

interface SispDriver
{
    public function name(): string;

    public function paymentEndpoint(): string;

    public function queryTransactionStatus(Transaction|string $transaction): TransactionStatusResponse;
}
