<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

final readonly class GenerateFingerprintAction
{
    public function __construct(private PostAutCode $postAutCode) {}

    public function handle(array $data): string
    {
        $content = $this->buildFingerprintContent($data);

        return base64_encode(hash('sha512', $content, true));
    }

    private function buildFingerprintContent(array $data): string
    {
        $parsedAmount = (int) ((float) $data['amount'] * 1000);

        return $this->postAutCode->handle()
            .($data['timeStamp'] ?? '')
            .$parsedAmount
            .($data['merchantRef'] ?? '')
            .($data['merchantSession'] ?? '')
            .($data['posID'] ?? '')
            .($data['currency'] ?? '')
            .($data['transactionCode'] ?? '');
    }
}
