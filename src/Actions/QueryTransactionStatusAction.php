<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Configuration\CredentialScope;
use Akira\Sisp\Drivers\SispManager;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\TransactionStatusResponse;

final readonly class QueryTransactionStatusAction
{
    public function __construct(
        private SispManager $manager,
        private CredentialScope $credentialScope,
    ) {}

    public function handle(Transaction|string $transaction): TransactionStatusResponse
    {
        if ($transaction instanceof Transaction) {
            return $this->credentialScope->forTransaction(
                $transaction,
                fn (): TransactionStatusResponse => $this->manager->driver()->queryTransactionStatus($transaction),
            );
        }

        return $this->manager->driver()->queryTransactionStatus($transaction);
    }
}
