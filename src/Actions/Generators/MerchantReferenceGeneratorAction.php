<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Generators;

use Akira\Sisp\Contracts\Generator;

final readonly class MerchantReferenceGeneratorAction implements Generator
{
    public function __invoke(): string
    {
        return 'R'.now()->format('YmdHis');
    }
}
