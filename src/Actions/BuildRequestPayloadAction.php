<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Akira\Sisp\ValueObjects\PaymentRequestData;

final readonly class BuildRequestPayloadAction
{
    public function __construct(
        private GenerateFingerprintAction $generateFingerprint,
    ) {}

    public function handle(PaymentRequestData $data): PaymentRequest
    {
        $merchantRef = $data->merchantRef ?? Sisp::getMerchantReference();
        $merchantSession = $data->merchantSession ?? Sisp::getMerchantSession();
        $amount = $data->amount;
        $timestamp = $data->timeStamp ?? Sisp::getTimeStamp();
        $currency = $data->currency ?? Sisp::getCurrency();
        $transactionCode = $data->transactionCode ?? Sisp::getDefaultTransactionCode();
        $token = $data->token ?? '';
        $entityCode = $data->entityCode ?? '';
        $referenceNumber = $data->referenceNumber ?? '';
        $locale = $data->locale ?? 'pt_PT';

        $payload = [
            'posID' => Sisp::getPosId(),
            'merchantRef' => $merchantRef,
            'merchantSession' => $merchantSession,
            'amount' => $amount,
            'currency' => $currency,
            'is3DSec' => Sisp::getIs3Dsec(),
            'urlMerchantResponse' => Sisp::getUrlMerchantResponse(),
            'languageMessages' => Sisp::getLanguageMessages(),
            'timeStamp' => $timestamp,
            'fingerprintversion' => Sisp::getFingerprintVersion(),
            'transactionCode' => $transactionCode,
            'token' => $token,
            'entityCode' => $entityCode,
            'referenceNumber' => $referenceNumber,
            'locale' => $locale,
        ];

        $payload['fingerprint'] = $this->generateFingerprint->handle($payload);

        return PaymentRequest::from($payload);
    }
}
