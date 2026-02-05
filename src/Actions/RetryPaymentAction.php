<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Akira\Sisp\ValueObjects\PaymentRequestData;

final readonly class RetryPaymentAction
{
    public function __construct(private BuildRequestPayloadAction $buildRequestPayload) {}

    public function handle(Transaction $transaction): PaymentRequest
    {
        $paymentRequestData = $this->extractFromTransaction($transaction);

        return $this->buildRequestPayload->handle($paymentRequestData);
    }

    private function extractFromTransaction(Transaction $transaction): PaymentRequestData
    {
        return new PaymentRequestData(
            amount: (float) $transaction->amount,
            merchantRef: $transaction->merchant_ref,
            merchantSession: $transaction->merchant_session,
            timeStamp: ($transaction->created_at ?? now())->format('Y-m-d H:i:s'),
            currency: $transaction->currency,
            transactionCode: $transaction->transaction_code,
            token: '',
            entityCode: '',
            referenceNumber: '',
        );
    }
}
