<?php

declare(strict_types=1);

namespace Akira\Sisp\Enums;

enum ChangeAccountAge: string
{
    case withoutAccount = '01';
    case accountCreatedDuringTransaction = '02';
    case accountCreatedLessThan30Days = '03';
    case accountCreatedBetween30And60Days = '04';
    case accountWithMoreThan60Days = '05';

    public function label(): string
    {
        return match ($this) {
            self::withoutAccount => __('Without Account'),
            self::accountCreatedDuringTransaction => __('Account Created During Transaction'),
            self::accountCreatedLessThan30Days => __('Account Created Less Than 30 Days'),
            self::accountCreatedBetween30And60Days => __('Account Created Between 30 And 60 Days'),
            self::accountWithMoreThan60Days => __('Account With More Than 60 Days'),
        };
    }
}
