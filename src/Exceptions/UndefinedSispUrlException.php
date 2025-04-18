<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

final class UndefinedSispUrlException extends Exception
{
    /**
     * Create a new UndefinedSispUrlException instance.
     */
    public function __construct()
    {
        parent::__construct(
            message: __('Undefined SISP URL, please check your configuration.'),
            code: Response::HTTP_BAD_REQUEST,
        );
    }
}
