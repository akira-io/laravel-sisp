<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Facades\Sisp;

final class PostAutCode
{
    /**
     * Encode the post authorization code, and return it
     */
    public static function encode(): string
    {
        $posAutCode = Sisp::getPosAutCode();

        return base64_encode(hash('sha512', $posAutCode, true));
    }
}
