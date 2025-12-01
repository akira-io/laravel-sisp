<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\ErrorMessageType;
use Akira\Sisp\ValueObjects\PaymentErrorResponse;

final readonly class GetPaymentErrorResponseAction
{
    public function handle(ErrorMessageType $errorType): PaymentErrorResponse
    {
        return new PaymentErrorResponse(
            code: $errorType->value,
            label: $errorType->label(),
            category: $errorType->category(),
            categoryLabel: $errorType->categoryLabel(),
            action: $errorType->action(),
            actionLabel: $errorType->actionLabel(),
        );
    }
}
