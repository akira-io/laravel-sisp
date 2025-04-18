<?php

declare(strict_types=1);

namespace Akira\Sisp\Enums;

enum ErrorMessageType: string
{
    case transactionError = '6';

    /**
     * Get the label for the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::transactionError => __('Transaction Error'),
        };
    }
}
