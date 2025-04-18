<?php

declare(strict_types=1);

namespace Akira\Sisp\Contracts;

interface Fields
{
    /**
     * Get the array of fields.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
