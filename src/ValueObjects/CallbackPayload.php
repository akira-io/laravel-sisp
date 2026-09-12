<?php

declare(strict_types=1);

namespace Akira\Sisp\ValueObjects;

final readonly class CallbackPayload
{
    public const string ERROR_MESSAGE_TYPE = '6';

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
        public bool $amountProvided = true,
        public string $errorCode = '',
        public string $errorDescription = '',
        public string $errorDetail = '',
        public string $screenError = '',
        public string $fingerprintVersion = '',
        public string $languageMessages = '',
        public bool $userCancelled = false,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function from(array $data): self
    {
        return new self(
            merchantRef: $data['merchantRespMerchantRef'] ?? $data['merchantRef'] ?? '',
            merchantSession: $data['merchantRespMerchantSession'] ?? $data['merchantSession'] ?? '',
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
            reloadCode: $data['merchantRespReloadCode'] ?? $data['reloadCode'] ?? '',
            currencyProvided: array_key_exists('currency', $data),
            transactionCodeProvided: array_key_exists('transactionCode', $data),
            posIDProvided: array_key_exists('posID', $data),
            amountProvided: array_key_exists('merchantRespPurchaseAmount', $data),
            errorCode: (string) ($data['merchantRespErrorCode'] ?? ''),
            errorDescription: (string) ($data['merchantRespErrorDescription'] ?? ''),
            errorDetail: (string) ($data['merchantRespErrorDetail'] ?? ''),
            screenError: (string) ($data['merchantRespScreenError'] ?? ''),
            fingerprintVersion: (string) ($data['resultFingerPrintVersion'] ?? ''),
            languageMessages: (string) ($data['languageMessages'] ?? ''),
            userCancelled: filter_var(
                $data['userCancelled'] ?? $data['UserCancelled'] ?? false,
                FILTER_VALIDATE_BOOL,
            ),
            raw: $data,
        );
    }

    public function isError(): bool
    {
        return $this->messageType === self::ERROR_MESSAGE_TYPE;
    }

    /**
     * @return array<string, mixed>
     */
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
            'merchantRespReloadCode' => $this->reloadCode,
            'merchantRespErrorCode' => $this->errorCode,
            'merchantRespErrorDescription' => $this->errorDescription,
            'merchantRespErrorDetail' => $this->errorDetail,
            'merchantRespScreenError' => $this->screenError,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function withoutFingerprint(): array
    {
        $data = $this->toArray();
        unset($data['resultFingerPrint']);

        return $data;
    }
}
