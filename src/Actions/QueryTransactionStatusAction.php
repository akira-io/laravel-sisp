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
        // A reference names a stored payment just as well as the model does,
        // so it is queried under the credentials that payment was built for.
        $stored = $transaction instanceof Transaction
            ? $transaction
            : Transaction::query()->where('merchant_ref', $transaction)->first();

        if (! $stored instanceof Transaction) {
            return $this->manager->driver()->queryTransactionStatus($transaction);
        }

        return $this->credentialScope->forTransaction(
            $stored,
            fn (): TransactionStatusResponse => $this->manager->driver()->queryTransactionStatus($transaction),
        );
    }
}
