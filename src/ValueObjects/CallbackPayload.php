<?php

declare(strict_types=1);

namespace Akira\Sisp\ValueObjects;

final readonly class CallbackPayload
{
    public function __construct(
        public string $merchantRef,
        public string $merchantSession,
        public string $timeStamp,
        public float $amount,
        public string $currency,
        public string $transactionCode,
        public string|int $transactionID,
        public string $messageType,
        public string $merchantResponse,
        public string $responseCode,
        public string $fingerprint,
        public string $posID,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            merchantRef: $data['merchantRespMerchantRef'] ?? $data['merchantRef'] ?? '',
            merchantSession: $data['merchantRespMerchantSession'] ?? $data['merchantSession'] ?? '',
            timeStamp: $data['merchantRespTimeStamp'] ?? $data['timeStamp'] ?? '',
            amount: (float) ($data['merchantRespPurchaseAmount'] ?? $data['amount'] ?? 0),
            currency: $data['currency'] ?? '',
            transactionCode: $data['transactionCode'] ?? '',
            transactionID: $data['merchantRespTid'] ?? $data['transactionID'] ?? '',
            messageType: $data['messageType'] ?? '',
            merchantResponse: $data['merchantResp'] ?? $data['merchantResponse'] ?? '',
            responseCode: $data['merchantRespCP'] ?? $data['responseCode'] ?? '',
            fingerprint: $data['resultFingerPrint'] ?? $data['fingerprint'] ?? '',
            posID: $data['posID'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'merchantRespMerchantRef' => $this->merchantRef,
            'merchantRespMerchantSession' => $this->merchantSession,
            'merchantRespTimeStamp' => $this->timeStamp,
            'merchantRespPurchaseAmount' => $this->amount,
            'currency' => $this->currency,
            'transactionCode' => $this->transactionCode,
            'merchantRespTid' => $this->transactionID,
            'messageType' => $this->messageType,
            'merchantResp' => $this->merchantResponse,
            'merchantRespCP' => $this->responseCode,
            'resultFingerPrint' => $this->fingerprint,
            'posID' => $this->posID,
        ];
    }

    public function withoutFingerprint(): array
    {
        $data = $this->toArray();
        unset($data['fingerprint']);

        return $data;
    }
}
