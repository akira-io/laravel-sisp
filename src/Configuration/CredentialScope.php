<?php

declare(strict_types=1);

namespace Akira\Sisp\Configuration;

use Akira\Sisp\Contracts\SispCredentialsResolver;
use Akira\Sisp\Contracts\TransactionCredentialsResolver;
use Akira\Sisp\Exceptions\UnknownMerchantCredentialsException;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\SispCredentials;
use Illuminate\Contracts\Container\Container;

/**
 * Runs a callback with a given set of credentials bound as the active
 * SispCredentialsResolver, and restores the previous binding afterwards.
 * Everything resolved from the container inside the callback (fingerprint
 * actions, pipes, drivers) sees those credentials.
 */
final readonly class CredentialScope
{
    public function __construct(
        private Container $container,
        private TransactionCredentialsResolver $transactionCredentials,
    ) {}

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function run(SispCredentials $credentials, callable $callback): mixed
    {
        $original = $this->container->make(SispCredentialsResolver::class);
        $this->container->instance(SispCredentialsResolver::class, new ScopedSispCredentialsResolver($credentials));

        try {
            return $callback();
        } finally {
            $this->container->instance(SispCredentialsResolver::class, $original);
        }
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     *
     * @throws UnknownMerchantCredentialsException
     */
    public function forTransaction(Transaction $transaction, callable $callback): mixed
    {
        return $this->run($this->transactionCredentials->resolveFor($transaction), $callback);
    }
}
