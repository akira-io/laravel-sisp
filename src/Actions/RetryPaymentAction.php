<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Configuration\CredentialScope;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Illuminate\Contracts\Container\Container;

final readonly class RetryPaymentAction
{
    private const string FALLBACK_POSTAL_CODE = '0000';

    public function __construct(
        private CredentialScope $credentialScope,
        private Container $container,
    ) {}

    public function handle(Transaction $transaction): PaymentRequest
    {
        $paymentRequestData = $this->extractFromTransaction($transaction);

        // The new attempt has to carry the posID the payment was built for:
        // its callback and later status queries are checked under that
        // merchant. The builder is made inside the scope, as it captures the
        // credentials resolver it is constructed with.
        return $this->credentialScope->forTransaction(
            $transaction,
            fn (): PaymentRequest => $this->container->make(BuildRequestPayloadAction::class)->handle($paymentRequestData),
        );
    }

    private function extractFromTransaction(Transaction $transaction): PaymentRequestData
    {
        return new PaymentRequestData(
            amount: $transaction->amount,
            merchantRef: $transaction->merchant_ref,
            merchantSession: $this->merchantSession($transaction),
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

    private function merchantSession(Transaction $transaction): ?string
    {
        $merchantSession = $transaction->getAttribute('merchant_session');

        return is_string($merchantSession) && $merchantSession !== '' ? $merchantSession : null;
    }
}
