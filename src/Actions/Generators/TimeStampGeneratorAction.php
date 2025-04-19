<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Generators;

use Akira\Sisp\Contracts\Generator;

final class TimeStampGeneratorAction implements Generator
{
    /**
     * Generate a unique timestamp.
     */
    public function __invoke(): string
    {
        return (string) now();
    }
}
