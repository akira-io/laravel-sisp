<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

final readonly class GenerateFingerprintAction
{
    public function __construct(private PostAutCode $postAutCode) {}

    public function handle(array $data): string
    {
        $content = $this->buildFingerprintContent($data);
        $fingerprint = base64_encode(hash('sha512', $content, true));

        \Log::debug('Request Fingerprint Generated', [
            'posAutCode_encoded' => substr($this->postAutCode->encode(), 0, 50) . '...',
            'timeStamp' => $data['timeStamp'] ?? '',
            'parsedAmount' => (int)((float)$data['amount'] * 1000),
            'merchantRef' => $data['merchantRef'] ?? '',
            'merchantSession' => $data['merchantSession'] ?? '',
            'posID' => $data['posID'] ?? '',
            'currency' => $data['currency'] ?? '',
            'transactionCode' => $data['transactionCode'] ?? '',
            'fingerprint' => $fingerprint,
        ]);

        return $fingerprint;
    }

    private function buildFingerprintContent(array $data): string
    {
        $parsedAmount = (int)((float)$data['amount'] * 1000);

        return $this->postAutCode->encode()
            . ($data['timeStamp'] ?? '')
            . $parsedAmount
            . ($data['merchantRef'] ?? '')
            . ($data['merchantSession'] ?? '')
            . ($data['posID'] ?? '')
            . ($data['currency'] ?? '')
            . ($data['transactionCode'] ?? '');
    }
}