<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Generators;

use Akira\Sisp\Contracts\Generator;

final readonly class MerchantSessionGeneratorAction implements Generator
{
    public function __invoke(): string
    {
        return 'S'.now()->format('YmdHis');
    }
}
