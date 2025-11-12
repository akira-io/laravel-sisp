<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->security();
arch()->preset()->laravel();

test('global')
    ->expect(['dd', 'dump', 'ray'])
    ->not->toBeUsed();
