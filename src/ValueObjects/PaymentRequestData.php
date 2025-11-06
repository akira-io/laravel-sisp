<?php

declare(strict_types=1);

namespace Akira\Sisp\ValueObjects;

final readonly class PaymentRequestData
{
    public function __construct(
        public float $amount,
        public ?string $merchantRef = null,
        public ?string $merchantSession = null,
        public ?string $timeStamp = null,
        public ?string $currency = null,
        public ?string $transactionCode = null,
        public ?string $token = null,
        public ?string $entityCode = null,
        public ?string $referenceNumber = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            amount: (float) $data['amount'],
            merchantRef: $data['merchantRef'] ?? null,
            merchantSession: $data['merchantSession'] ?? null,
            timeStamp: $data['timeStamp'] ?? null,
            currency: $data['currency'] ?? null,
            transactionCode: $data['transactionCode'] ?? null,
            token: $data['token'] ?? null,
            entityCode: $data['entityCode'] ?? null,
            referenceNumber: $data['referenceNumber'] ?? null,
        );
    }
}
