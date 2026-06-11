<?php

declare(strict_types=1);

namespace Akira\Sisp\Contracts;

use Akira\Sisp\Pipelines\Callback\CallbackContext;
use Closure;

interface CallbackPipe
{
    /**
     * @param  Closure(CallbackContext): CallbackContext  $next
     */
    public function handle(CallbackContext $context, Closure $next): CallbackContext;
}
