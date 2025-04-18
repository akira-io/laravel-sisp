<?php

declare(strict_types=1);

namespace Akira\Sisp\Contracts;

interface FingerPrint
{
    /**
     * Create a new FingerPrint instance.
     */
    public function __construct(Fields $field);

    /**
     * Get the fingerprint data.
     */
    public function get(): string;
}
