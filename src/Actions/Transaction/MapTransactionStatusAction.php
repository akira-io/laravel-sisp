<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transaction;

use Akira\Sisp\Enums\ErrorMessageType;
use Akira\Sisp\Enums\SuccessMessageType;
use Akira\Sisp\Enums\TransactionStatus;
use Illuminate\Support\Collection;

final class MapTransactionStatusAction
{
    public function handle(?string $messageType): TransactionStatus
    {
        return match (true) {
            $this->getTransactionSuccessValues()->contains($messageType) => TransactionStatus::completed,
            $this->getTransactionErrorValues()->contains($messageType) => TransactionStatus::failed,
            default => TransactionStatus::pending,
        };
    }

    private function getTransactionErrorValues(): Collection
    {

        return collect(ErrorMessageType::cases())
            ->map(fn (ErrorMessageType $case) => $case->value);
    }

    private function getTransactionSuccessValues(): Collection
    {

        return collect(SuccessMessageType::cases())
            ->map(fn (SuccessMessageType $case) => $case->value);
    }
}
