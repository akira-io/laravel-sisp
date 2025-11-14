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
            self::purchase => __('sisp::messages.success.labels.purchase'),
            self::servicePayment => __('sisp::messages.success.labels.servicePayment'),
            self::phoneRecharge => __('sisp::messages.success.labels.phoneRecharge'),
            self::enrollmentRequest => __('sisp::messages.success.labels.enrollmentRequest'),
            self::tokenPayment => __('sisp::messages.success.labels.tokenPayment'),
            self::tokenCancel => __('sisp::messages.success.labels.tokenCancel'),
        };
    }
}
