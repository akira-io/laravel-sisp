<?php

declare(strict_types=1);

namespace Akira\Sisp\Enums;

enum ErrorMessageType: string
{
    case transactionError = '6';

    public function label(): string
    {
        return match ($this) {
            self::transactionError => __('Transaction Error'),
        };
    }
}
