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
        $posAutCode = $this->postAutCode->handle();
        $messageType = $payload['messageType'] ?? '';
        $clearingPeriod = $payload['clearingPeriod'] ?? '';
        $amount = $payload['amount'] ?? '';
        $dateTime = $payload['dateTime'] ?? '';
        $merchantRef = $payload['merchantRef'] ?? '';
        $pan = $payload['pan'] ?? '';
        $posID = $payload['posID'] ?? '';
        $responseCode = $payload['responseCode'] ?? '';

        return $posAutCode
            .$messageType
            .$clearingPeriod
            .$amount
            .$dateTime
            .$merchantRef
            .$pan
            .$posID
            .$responseCode;
    }
}
