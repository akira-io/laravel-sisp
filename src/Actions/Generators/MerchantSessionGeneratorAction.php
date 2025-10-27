<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Generators;

use Akira\Sisp\Contracts\Generator;
use Illuminate\Support\Str;

final readonly class MerchantSessionGeneratorAction implements Generator
{
    public function __invoke(): string
    {
        return Str::random(32);
    }
}