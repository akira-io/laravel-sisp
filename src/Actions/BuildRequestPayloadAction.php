<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Contracts\SispCredentialsResolver;
use Akira\Sisp\Exceptions\MissingThreeDSecureDataException;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Akira\Sisp\ValueObjects\ThreeDSecureData;

final readonly class BuildRequestPayloadAction
{
    public function __construct(
        private GenerateFingerprintAction $generateFingerprint,
        private BuildPurchaseRequestAction $buildPurchaseRequest,
        private SispCredentialsResolver $resolver,
    ) {}

    public function handle(PaymentRequestData $data): PaymentRequest
    {
        $credentials = $this->resolver->resolve();

        $merchantRef = $data->merchantRef ?? Sisp::getMerchantReference();
        $merchantSession = $data->merchantSession ?? Sisp::getMerchantSession();
        $amount = $data->amount;
        $timestamp = $data->timeStamp ?? Sisp::getTimeStamp();
        $currency = $data->currency ?? $credentials->currency;
        $transactionCode = $data->transactionCode ?? Sisp::getDefaultTransactionCode();
        $token = $data->token ?? '';
        $entityCode = $data->entityCode ?? '';
        $referenceNumber = $data->referenceNumber ?? '';
        $locale = $data->locale ?? 'pt_PT';

        $payload = [
            'posID' => $credentials->posId,
            'merchantRef' => $merchantRef,
            'merchantSession' => $merchantSession,
            'amount' => $amount,
            'currency' => $currency,
            'is3DSec' => $credentials->is3DSec,
            'urlMerchantResponse' => $credentials->urlMerchantResponse ?? route('sisp.callback'),
            'languageMessages' => $credentials->languageMessages,
            'timeStamp' => $timestamp,
            'fingerprintversion' => $credentials->fingerprintVersion,
            'transactionCode' => $transactionCode,
            'token' => $token,
            'entityCode' => $entityCode,
            'referenceNumber' => $referenceNumber,
            'locale' => $locale,
        ];

        $payload['purchaseRequest'] = $this->buildPurchaseRequestIfNeeded($data);
        $payload['fingerprint'] = $this->generateFingerprint->handle($payload);

        return PaymentRequest::from($payload);
    }

    /**
     * @throws MissingThreeDSecureDataException
     */
    private function buildPurchaseRequestIfNeeded(PaymentRequestData $data): string
    {
        $credentials = $this->resolver->resolve();

        if ($credentials->is3DSec !== '1') {
            return '';
        }

        if (! $data->hasThreeDSecureData()) {
            throw new MissingThreeDSecureDataException(
                $data->getMissingThreeDSecureFields()
            );
        }

        $threeDSecureData = ThreeDSecureData::fromCustomerData(
            email: $data->customerEmail,
            country: $data->customerCountry,
            city: $data->customerCity,
            address: $data->customerAddress,
            postalCode: $data->customerPostalCode,
            phone: $data->customerPhone,
        );

        return $this->buildPurchaseRequest->handle($threeDSecureData);
    }
}
