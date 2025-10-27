<?php

declare(strict_types=1);

namespace Akira\Sisp\Contracts;

interface Generator
{
    /**
     * Generate a unique identifier.
     */
    public function __invoke(): string;
}