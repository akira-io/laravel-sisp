<?php

declare(strict_types=1);

namespace Akira\Sisp\ValueObjects;

final readonly class CallbackPayload
{
    public function __construct(
        public string $merchantRef,
        public string $merchantSession,
        public string $timeStamp,
        public string|float $amount,
        public string $currency,
        public string $transactionCode,
        public string|int $transactionID,
        public string $messageType,
        public string $merchantResponse,
        public string $responseCode,
        public string $fingerprint,
        public string $posID,
        public string $messageID = '',
        public string $pan = '',
        public string $clearingPeriod = '',
        public string $reference = '',
        public string $entityCode = '',
        public string $clientReceipt = '',
        public string $additionalErrorMessage = '',
        public string $merchantRespCp = '',
        public string $reloadCode = '',
        public bool $currencyProvided = true,
        public bool $transactionCodeProvided = true,
        public bool $posIDProvided = true,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            merchantRef: $data['merchantRespMerchantRef'] ?? '',
            merchantSession: $data['merchantRespMerchantSession'] ?? '',
            timeStamp: $data['merchantRespTimeStamp'] ?? '',
            amount: ($data['merchantRespPurchaseAmount'] ?? 0),
            currency: $data['currency'] ?? '',
            transactionCode: $data['transactionCode'] ?? '',
            transactionID: $data['merchantRespTid'] ?? '',
            messageType: $data['messageType'] ?? '',
            merchantResponse: $data['merchantResp'] ?? '',
            responseCode: $data['merchantRespCP'] ?? '',
            fingerprint: $data['resultFingerPrint'] ?? '',
            posID: $data['posID'] ?? '',
            messageID: $data['merchantRespMessageID'] ?? '',
            pan: $data['merchantRespPan'] ?? '',
            clearingPeriod: $data['merchantRespCP'] ?? '',
            reference: $data['merchantRespReferenceNumber'] ?? '',
            entityCode: $data['merchantRespEntityCode'] ?? '',
            clientReceipt: $data['merchantRespClientReceipt'] ?? '',
            additionalErrorMessage: $data['merchantRespAdditionalErrorMessage'] ?? '',
            merchantRespCp: $data['merchantRespCP'] ?? '',
            reloadCode: $data['reloadCode'] ?? '',
            currencyProvided: array_key_exists('currency', $data),
            transactionCodeProvided: array_key_exists('transactionCode', $data),
            posIDProvided: array_key_exists('posID', $data),
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
            'merchantRespCP' => $this->merchantRespCp,
            'resultFingerPrint' => $this->fingerprint,
            'posID' => $this->posID,
            'merchantRespMessageID' => $this->messageID,
            'merchantRespPan' => $this->pan,
            'merchantRespReferenceNumber' => $this->reference,
            'merchantRespEntityCode' => $this->entityCode,
            'merchantRespClientReceipt' => $this->clientReceipt,
            'merchantRespAdditionalErrorMessage' => $this->additionalErrorMessage,
            'reloadCode' => $this->reloadCode,

        ];
    }

    public function withoutFingerprint(): array
    {
        $data = $this->toArray();
        unset($data['resultFingerPrint']);

        return $data;
    }
}
