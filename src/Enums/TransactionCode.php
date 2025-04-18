<?php

declare(strict_types=1);

namespace Akira\Sisp\Enums;

enum TransactionCode: string
{
    case purchase = '1';
    case servicePayment = '2';
    case phoneRecharge = '3';
    case enrollmentRequest = '5';
    case tokenPurchase = '6';
    case tokenCancel = '7';

    /**
     * Get the label for the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::purchase => __('Purchase'),
            self::servicePayment => __('Service Payment'),
            self::phoneRecharge => __('Phone Recharge'),
            self::enrollmentRequest => __('Enrollment Request'),
            self::tokenPurchase => __('Token Purchase'),
            self::tokenCancel => __('Token Cancel'),
        };

    }
}
