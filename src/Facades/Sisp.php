<?php

namespace Akira\Sisp\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Akira\Sisp\Sisp
 */
class Sisp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Akira\Sisp\Sisp::class;
    }
}
