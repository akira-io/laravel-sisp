<?php

declare(strict_types=1);

namespace Akira\Sisp\ValueObjects;

use Akira\Sisp\Enums\TransactionStatus;

final readonly class TransactionStatusResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public bool $result,
        public bool $transactionSuccess,
        public string $transactionStatusDescription,
        public string $message,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function from(array $data): self
    {
        return new self(
            result: (bool) ($data['result'] ?? false),
            transactionSuccess: (bool) ($data['transactionSuccess'] ?? false),
            transactionStatusDescription: (string) ($data['transactionStatusDescription'] ?? ''),
            message: (string) ($data['msg'] ?? ''),
            raw: $data,
        );
    }

    public function paymentStatus(): TransactionStatus
    {
        if (! $this->result) {
            return TransactionStatus::pending;
        }

        return $this->transactionSuccess
            ? TransactionStatus::completed
            : TransactionStatus::failed;
    }
}
