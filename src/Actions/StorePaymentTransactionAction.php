<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Akira\Sisp\ValueObjects\TransactionData;

final readonly class StorePaymentTransactionAction
{
    public function __construct(
        private CreateTransactionAction $createTransaction,
    ) {}

    public function handle(PaymentRequest $paymentRequest, bool $recordAttempt = true): Transaction
    {
        $transactionData = TransactionData::from([
            'merchantRef' => $paymentRequest->merchantRef,
            'merchantSession' => $paymentRequest->merchantSession,
            'amount' => $paymentRequest->amount,
            'currency' => $paymentRequest->currency,
            'transactionCode' => $paymentRequest->transactionCode,
            'payload' => $paymentRequest->toArray(),
            'locale' => $paymentRequest->locale,
        ]);

        return $this->createTransaction->handle($transactionData, $recordAttempt);
    }
}
