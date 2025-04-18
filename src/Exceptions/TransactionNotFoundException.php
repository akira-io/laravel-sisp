<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

final class TransactionNotFoundException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct()
    {
        parent::__construct(message: __('Transaction not found'), code: Response::HTTP_BAD_REQUEST);
    }
}
