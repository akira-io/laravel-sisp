<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Generators;

final class TimeStampGeneratorAction
{
    /**
     * Generate a unique timestamp.
     */
    public function __invoke(): string
    {
        return (string) now();
    }
}
