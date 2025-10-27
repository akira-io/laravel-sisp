<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Log;

final readonly class ValidateFingerprintAction
{
    public function __construct(private PostAutCode $postAutCode) {}

    public function handle(array $payload, string $receivedFingerprint): bool
    {
        $computedFingerprint = $this->computeFingerprint($payload);

        Log::debug('Response Fingerprint Validation', [
            'payload_keys' => array_keys($payload),
            'received' => $receivedFingerprint,
            'computed' => $computedFingerprint,
            'match' => hash_equals($computedFingerprint, $receivedFingerprint),
        ]);

        return hash_equals($computedFingerprint, $receivedFingerprint);
    }

    public function computeFingerprint(array $payload): string
    {
        $content = $this->buildFingerprintContent($payload);

        return base64_encode(hash('sha512', $content, true));
    }

    private function buildFingerprintContent(array $payload): string
    {
        $messageType = mb_trim($payload['messageType'] ?? '');

        if (in_array($messageType, ['8', '10', 'M', 'P'])) {
            return $this->buildSuccessFingerprint($payload);
        }
        if ($messageType === '6') {
            return $this->buildErrorFingerprint($payload);
        }

        return '';
    }

    private function buildSuccessFingerprint(array $payload): string
    {
        $merchantRespCP = ! empty($payload['merchantRespCP']) ? (int) mb_trim($payload['merchantRespCP']) : '';
        $merchantRespTid = ! empty($payload['merchantRespTid']) ? (int) mb_trim($payload['merchantRespTid']) : '';
        $merchantRespMerchantRef = mb_trim($payload['merchantRespMerchantRef'] ?? '');
        $merchantRespMerchantSession = mb_trim($payload['merchantRespMerchantSession'] ?? '');
        $merchantRespMessageID = mb_trim($payload['merchantRespMessageID'] ?? '');
        $merchantRespPan = mb_trim($payload['merchantRespPan'] ?? '');
        $merchantResp = mb_trim($payload['merchantResp'] ?? '');
        $merchantRespTimeStamp = $payload['merchantRespTimeStamp'] ?? '';
        $entityCode = ! empty($payload['merchantRespEntityCode']) ? (int) mb_trim($payload['merchantRespEntityCode']) : '';
        $referenceNumber = ! empty($payload['merchantRespReferenceNumber']) ? (int) mb_trim($payload['merchantRespReferenceNumber']) : '';
        $merchantRespClientReceipt = mb_trim($payload['merchantRespClientReceipt'] ?? '');
        $merchantRespAdditionalErrorMessage = mb_trim($payload['merchantRespAdditionalErrorMessage'] ?? '');
        $merchantRespReloadCode = mb_trim($payload['merchantRespReloadCode'] ?? '');

        $purchaseAmount = $this->parseAmount($payload);

        return $this->postAutCode->handle()
            .mb_trim($payload['messageType'] ?? '')
            .$merchantRespCP
            .$merchantRespTid
            .$merchantRespMerchantRef
            .$merchantRespMerchantSession
            .$purchaseAmount
            .$merchantRespMessageID
            .$merchantRespPan
            .$merchantResp
            .$merchantRespTimeStamp
            .$referenceNumber
            .$entityCode
            .$merchantRespClientReceipt
            .$merchantRespAdditionalErrorMessage
            .$merchantRespReloadCode;
    }

    private function buildErrorFingerprint(array $payload): string
    {
        $merchantRespMessageID = mb_trim($payload['merchantRespMessageID'] ?? '');
        $merchantRespErrorCode = mb_trim($payload['merchantRespErrorCode'] ?? '');
        $merchantRespErrorDetail = mb_trim($payload['merchantRespErrorDetail'] ?? '');
        $merchantRespErrorDescription = mb_trim($payload['merchantRespErrorDescription'] ?? '');
        $merchantRespMerchantRef = mb_trim($payload['merchantRespMerchantRef'] ?? '');
        $merchantRespMerchantSession = mb_trim($payload['merchantRespMerchantSession'] ?? '');
        $merchantRespAdditionalErrorMessage = mb_trim($payload['merchantRespAdditionalErrorMessage'] ?? '');
        $merchantRespTimeStamp = $payload['merchantRespTimeStamp'] ?? '';

        return $this->postAutCode->handle()
            .mb_trim($payload['messageType'] ?? '')
            .$merchantRespMessageID
            .$merchantRespErrorCode
            .$merchantRespErrorDetail
            .$merchantRespErrorDescription
            .$merchantRespMerchantRef
            .$merchantRespMerchantSession
            .$merchantRespAdditionalErrorMessage
            .$merchantRespTimeStamp;
    }

    private function parseAmount(array $payload): int
    {
        $amount = $payload['merchantRespPurchaseAmount'] ?? 0;

        return (int) ((float) $amount * 1000);
    }
}
