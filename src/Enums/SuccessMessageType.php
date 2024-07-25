<?php

declare(strict_types=1);

namespace Akira\Sisp\Enums;

enum SuccessMessageType: string
{
    case purchase = '8';
    case servicePayment = 'P';
    case phoneRecharge = 'M';
    case enrollmentRequest = 'A';
    case tokenPayment = 'B';
    case tokenCancel = 'C';

    public function label(): string
    {
        return match ($this) {
            self::purchase => __('Purchase'),
            self::servicePayment => __('Service Payment'),
            self::phoneRecharge => __('Phone Recharge'),
            self::enrollmentRequest => __('Enrollment Request'),
            self::tokenPayment => __('Token Payment'),
            self::tokenCancel => __('Token Cancel'),
        };
    }
}
