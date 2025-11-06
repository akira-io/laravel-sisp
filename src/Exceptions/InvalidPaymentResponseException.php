<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

final class InvalidPaymentResponseException extends Exception
{
    public function __construct(?string $message = null)
    {
        parent::__construct(
            message: $message ?? __('Invalid payment response'),
            code: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}
