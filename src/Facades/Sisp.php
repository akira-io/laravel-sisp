<?php

declare(strict_types=1);

namespace Akira\Sisp\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Akira\Sisp\Sisp
 */
final class Sisp extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Akira\Sisp\Sisp::class;
    }
}
