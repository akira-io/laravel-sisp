<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Generators;

use Akira\Sisp\Contracts\Generator;

final class MerchantSessionGeneratorAction implements Generator
{
    /**
     * Generate a unique merchant session ID.
     */
    public function __invoke(): string
    {
        return mb_trim('S'.now()->format('YmdHis'));
    }
}
