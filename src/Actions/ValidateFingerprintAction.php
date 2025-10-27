<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

final readonly class ValidateFingerprintAction
{
    public function __construct(private PostAutCode $postAutCode) {}

    public function handle(array $payload, string $receivedFingerprint): bool
    {
        $computedFingerprint = $this->computeFingerprint($payload);

        \Log::debug('Response Fingerprint Validation', [
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
        $messageType = trim($payload['messageType'] ?? '');

        if (in_array($messageType, ['8', '10', 'M', 'P'])) {
            return $this->buildSuccessFingerprint($payload);
        } elseif ($messageType === '6') {
            return $this->buildErrorFingerprint($payload);
        }

        return '';
    }

    private function buildSuccessFingerprint(array $payload): string
    {
        $merchantRespCP = !empty($payload['merchantRespCP']) ? (int)trim($payload['merchantRespCP']) : '';
        $merchantRespTid = !empty($payload['merchantRespTid']) ? (int)trim($payload['merchantRespTid']) : '';
        $merchantRespMerchantRef = trim($payload['merchantRespMerchantRef'] ?? '');
        $merchantRespMerchantSession = trim($payload['merchantRespMerchantSession'] ?? '');
        $merchantRespMessageID = trim($payload['merchantRespMessageID'] ?? '');
        $merchantRespPan = trim($payload['merchantRespPan'] ?? '');
        $merchantResp = trim($payload['merchantResp'] ?? '');
        $merchantRespTimeStamp = $payload['merchantRespTimeStamp'] ?? '';
        $entityCode = !empty($payload['merchantRespEntityCode']) ? (int)trim($payload['merchantRespEntityCode']) : '';
        $referenceNumber = !empty($payload['merchantRespReferenceNumber']) ? (int)trim($payload['merchantRespReferenceNumber']) : '';
        $merchantRespClientReceipt = trim($payload['merchantRespClientReceipt'] ?? '');
        $merchantRespAdditionalErrorMessage = trim($payload['merchantRespAdditionalErrorMessage'] ?? '');
        $merchantRespReloadCode = trim($payload['merchantRespReloadCode'] ?? '');

        $purchaseAmount = $this->parseAmount($payload);

        return $this->postAutCode->encode()
            . trim($payload['messageType'] ?? '')
            . $merchantRespCP
            . $merchantRespTid
            . $merchantRespMerchantRef
            . $merchantRespMerchantSession
            . $purchaseAmount
            . $merchantRespMessageID
            . $merchantRespPan
            . $merchantResp
            . $merchantRespTimeStamp
            . $referenceNumber
            . $entityCode
            . $merchantRespClientReceipt
            . $merchantRespAdditionalErrorMessage
            . $merchantRespReloadCode;
    }

    private function buildErrorFingerprint(array $payload): string
    {
        $merchantRespMessageID = trim($payload['merchantRespMessageID'] ?? '');
        $merchantRespErrorCode = trim($payload['merchantRespErrorCode'] ?? '');
        $merchantRespErrorDetail = trim($payload['merchantRespErrorDetail'] ?? '');
        $merchantRespErrorDescription = trim($payload['merchantRespErrorDescription'] ?? '');
        $merchantRespMerchantRef = trim($payload['merchantRespMerchantRef'] ?? '');
        $merchantRespMerchantSession = trim($payload['merchantRespMerchantSession'] ?? '');
        $merchantRespAdditionalErrorMessage = trim($payload['merchantRespAdditionalErrorMessage'] ?? '');
        $merchantRespTimeStamp = $payload['merchantRespTimeStamp'] ?? '';

        return $this->postAutCode->encode()
            . trim($payload['messageType'] ?? '')
            . $merchantRespMessageID
            . $merchantRespErrorCode
            . $merchantRespErrorDetail
            . $merchantRespErrorDescription
            . $merchantRespMerchantRef
            . $merchantRespMerchantSession
            . $merchantRespAdditionalErrorMessage
            . $merchantRespTimeStamp;
    }

    private function parseAmount(array $payload): int
    {
        $amount = $payload['merchantRespPurchaseAmount'] ?? 0;

        return (int)((float)$amount * 1000);
    }
}