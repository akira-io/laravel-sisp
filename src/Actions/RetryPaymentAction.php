<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Akira\Sisp\ValueObjects\PaymentRequestData;

final readonly class RetryPaymentAction
{
    private const string FALLBACK_POSTAL_CODE = '0000';

    public function __construct(private BuildRequestPayloadAction $buildRequestPayload) {}

    public function handle(Transaction $transaction): PaymentRequest
    {
        $paymentRequestData = $this->extractFromTransaction($transaction);

        return $this->buildRequestPayload->handle($paymentRequestData);
    }

    private function extractFromTransaction(Transaction $transaction): PaymentRequestData
    {
        return new PaymentRequestData(
            amount: $transaction->amount,
            merchantRef: $transaction->merchant_ref,
            merchantSession: null,
            timeStamp: null,
            currency: $transaction->currency,
            transactionCode: $transaction->transaction_code,
            token: '',
            entityCode: '',
            referenceNumber: '',
            locale: $transaction->locale,
            customerEmail: $transaction->customer_email,
            customerCountry: $transaction->customer_country,
            customerCity: $transaction->customer_city,
            customerAddress: $transaction->customer_address,
            customerPostalCode: $this->customerPostalCode($transaction),
            customerPhone: $transaction->customer_phone,
        );
    }

    private function customerPostalCode(Transaction $transaction): string
    {
        $postalCode = $transaction->getAttribute('customer_postal_code');

        return is_string($postalCode) && $postalCode !== '' ? $postalCode : self::FALLBACK_POSTAL_CODE;
    }
}
