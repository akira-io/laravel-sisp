<?php

declare(strict_types=1);

namespace Akira\Sisp\Support;

use Inertia\Inertia;

final readonly class InertiaAvailability
{
    public function __construct(private bool $available = true) {}

    public function available(): bool
    {
        return $this->available && class_exists(Inertia::class);
    }
}
