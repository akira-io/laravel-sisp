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
    case refund = '10';
    case partialRefund = '?';

    public function label(): string
    {
        return match ($this) {
            self::purchase => __('sisp::messages.success.labels.purchase'),
            self::servicePayment => __('sisp::messages.success.labels.servicePayment'),
            self::phoneRecharge => __('sisp::messages.success.labels.phoneRecharge'),
            self::enrollmentRequest => __('sisp::messages.success.labels.enrollmentRequest'),
            self::tokenPayment => __('sisp::messages.success.labels.tokenPayment'),
            self::tokenCancel => __('sisp::messages.success.labels.tokenCancel'),
            self::refund => __('sisp::messages.success.labels.refund'),
            self::partialRefund => __('sisp::messages.success.labels.partialRefund'),
        };
    }

    /** @return array<int, string> */
    public function expectedMerchantResponses(): array
    {
        return match ($this) {
            self::purchase, self::servicePayment, self::phoneRecharge => ['C'],
            self::enrollmentRequest, self::tokenPayment, self::tokenCancel => ['0'],
            self::refund, self::partialRefund => [''],
        };
    }
}
