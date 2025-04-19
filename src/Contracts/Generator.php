<?php

declare(strict_types=1);

namespace Akira\Sisp\Contracts;

interface Generator
{
    public function __invoke(): string;
}
