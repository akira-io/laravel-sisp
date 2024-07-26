<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

class PostAutCode
{
    public static function encode(): string
    {
        $posAutCode = config('sisp.posAutCode');

        return base64_encode(hash('sha512', $posAutCode, true));
    }
}
