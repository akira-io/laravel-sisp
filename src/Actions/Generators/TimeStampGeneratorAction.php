<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Generators;

use Akira\Sisp\Contracts\Generator;

final readonly class TimeStampGeneratorAction implements Generator
{
    public function __invoke(): string
    {
        return date('YmdHis');
    }
}
