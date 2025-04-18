<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

final class InvalidPaymentResponseException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct()
    {
        parent::__construct(
            message: __('Invalid payment response'),
            code: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}
