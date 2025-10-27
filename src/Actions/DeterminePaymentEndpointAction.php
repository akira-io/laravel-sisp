<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Facades\Sisp;

final readonly class DeterminePaymentEndpointAction
{
    public function handle(): string
    {
        if (config('sisp.sandbox', false)) {
            return route('sisp.sandbox');
        }

        return Sisp::getUri();
    }
}