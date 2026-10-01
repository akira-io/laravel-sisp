<?php

declare(strict_types=1);

namespace Akira\Sisp\Configuration;

use Akira\Sisp\Contracts\SispCredentialsResolver;
use Akira\Sisp\Contracts\TransactionCredentialsResolver;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\SispCredentials;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;

/**
 * Answers with the credentials currently bound. The package cannot know
 * another merchant's posAutCode, so a transaction built for a different
 * posID gets the same answer with a warning: a multi-merchant application
 * binds its own TransactionCredentialsResolver to look each merchant up.
 */
final readonly class PosIdTransactionCredentialsResolver implements TransactionCredentialsResolver
{
    public function __construct(private Container $container) {}

    public function resolveFor(Transaction $transaction): SispCredentials
    {
        $current = $this->container->make(SispCredentialsResolver::class)->resolve();
        $posId = $transaction->posId();

        if ($posId !== null && $posId !== $current->posId) {
            Log::warning('SISP transaction was built for another posID; using the active credentials. Bind TransactionCredentialsResolver to resolve per merchant.', [
                'transaction_id' => $transaction->getKey(),
                'pos_id' => $posId,
                'active_pos_id' => $current->posId,
            ]);
        }

        return $current;
    }
}
