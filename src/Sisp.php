<?php

declare(strict_types=1);

namespace Akira\Sisp;

use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Services\PaymentValidator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

final readonly class Sisp
{
    public function __construct(
        private LoadConfig $config,
        private PaymentValidator $validator,
    ) {}

    /**
     * Get all transactions from the database.
     *
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return Transaction::get();
    }

    /**
     * Request a payment to the SISP Gateway.
     */
    public function requestPayment(float $amount, string $transactionId, array $details = []): RedirectResponse|Redirector
    {
        return to_route('sisp.payment.request', [
            'amount' => $amount,
            'transactionId' => $transactionId,
            'details' => $details,
        ]);
    }

    /**
     * Check if payment request is successful.
     */
    public function paymentIsSuccessful(Request $request): bool
    {
        return $this->validator->isSuccessful($request);
    }

    /**
     * Check if payment was cancelled by user.
     */
    public function paymentIsCancelled(Request $request): bool
    {
        return $this->validator->isCancelled($request);
    }

    /**
     * Get configuration manager.
     */
    public function config(): LoadConfig
    {
        return $this->config;
    }
}
