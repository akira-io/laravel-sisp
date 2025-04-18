<?php

declare(strict_types=1);

namespace Akira\Sisp\Enums;

enum SuspiciousAccountActivity: string
{
    case noneSuspect = '01';
    case suspect = '02';

    /**
     * Get the label for the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::noneSuspect => __('None Suspect'),
            self::suspect => __('Suspect'),
        };
    }
}
