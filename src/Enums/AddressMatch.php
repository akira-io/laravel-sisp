<?php

declare(strict_types=1);

namespace Akira\Sisp\Enums;

enum AddressMatch: string
{
    case yes = 'Y';
    case no = 'N';

    public function label(): string
    {
        return match ($this) {
            self::yes => __('Yes'),
            self::no => __('No'),
        };
    }
}
