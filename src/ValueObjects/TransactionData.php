<?php

declare(strict_types=1);

namespace Akira\Sisp\ValueObjects;

final readonly class TransactionData
{
    public function __construct(
        public string $merchantRef,
        public string $merchantSession,
        public float $amount,
        public string $currency = '132',
        public string $transactionCode = '1',
        public array $payload = [],
        public ?string $locale = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            merchantRef: $data['merchantRef'],
            merchantSession: $data['merchantSession'],
            amount: (float) $data['amount'],
            currency: $data['currency'] ?? '132',
            transactionCode: $data['transactionCode'] ?? '1',
            payload: $data['payload'] ?? [],
            locale: $data['locale'] ?? null,
        );
    }
}
