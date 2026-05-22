<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Models\Transaction;

final readonly class CanRetryPaymentAction
{
    public function __construct(private LoadConfig $config) {}

    public function handle(Transaction $transaction): bool
    {
        if (! $this->config->isRetryAllowed()) {
            return false;
        }

        if ($this->config->getIs3Dsec() !== '1') {
            return true;
        }

        return ! $this->isMissingRequiredThreeDSecureData($transaction);
    }

    private function isMissingRequiredThreeDSecureData(Transaction $transaction): bool
    {
        return blank($transaction->customer_email)
            || blank($transaction->customer_country)
            || blank($transaction->customer_city)
            || blank($transaction->customer_address)
            || blank($transaction->getAttribute('customer_postal_code'));
    }
}
