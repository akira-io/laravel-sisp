<?php

declare(strict_types=1);

namespace Akira\Sisp\Contracts;

use Akira\Sisp\Configuration\PosIdTransactionCredentialsResolver;
use Akira\Sisp\Exceptions\UnknownMerchantCredentialsException;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\SispCredentials;
use Illuminate\Container\Attributes\Bind;

/**
 * Resolves the credentials a stored transaction was created with, so the
 * callback, the status query and the refund request of a payment started
 * with Sisp::forCredentials() use that merchant's posID and posAutCode.
 */
#[Bind(PosIdTransactionCredentialsResolver::class)]
interface TransactionCredentialsResolver
{
    /**
     * @throws UnknownMerchantCredentialsException when no credentials are known for the transaction's merchant
     */
    public function resolveFor(Transaction $transaction): SispCredentials;
}
