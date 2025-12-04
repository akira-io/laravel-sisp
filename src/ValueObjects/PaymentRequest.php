<?php

declare(strict_types=1);

namespace Akira\Sisp\ValueObjects;

final readonly class PaymentRequest
{
    public function __construct(
        public string $posID,
        public string $merchantRef,
        public string $merchantSession,
        public int|float $amount,
        public string $currency,
        public string $is3DSec,
        public string $urlMerchantResponse,
        public string $languageMessages,
        public string $timeStamp,
        public string $fingerprintversion,
        public string $transactionCode,
        public string $fingerprint,
        public string $token = '',
        public string $entityCode = '',
        public string $referenceNumber = '',
        public string $locale = 'pt',
    ) {}

    public static function from(array $data): self
    {
        return new self(
            posID: $data['posID'],
            merchantRef: $data['merchantRef'],
            merchantSession: $data['merchantSession'],
            amount: $data['amount'],
            currency: $data['currency'],
            is3DSec: $data['is3DSec'],
            urlMerchantResponse: $data['urlMerchantResponse'],
            languageMessages: $data['languageMessages'],
            timeStamp: $data['timeStamp'],
            fingerprintversion: $data['fingerprintversion'],
            transactionCode: $data['transactionCode'],
            fingerprint: $data['fingerprint'],
            token: $data['token'] ?? '',
            entityCode: $data['entityCode'] ?? '',
            referenceNumber: $data['referenceNumber'] ?? '',
            locale: $data['locale'] ?? 'pt',
        );
    }

    public function toArray(): array
    {
        return [
            'posID' => $this->posID,
            'merchantRef' => $this->merchantRef,
            'merchantSession' => $this->merchantSession,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'is3DSec' => $this->is3DSec,
            'urlMerchantResponse' => $this->urlMerchantResponse,
            'languageMessages' => $this->languageMessages,
            'timeStamp' => $this->timeStamp,
            'fingerprintversion' => $this->fingerprintversion,
            'transactionCode' => $this->transactionCode,
            'fingerprint' => $this->fingerprint,
            'token' => $this->token,
            'entityCode' => $this->entityCode,
            'referenceNumber' => $this->referenceNumber,
            'locale' => $this->locale,
        ];
    }
}
