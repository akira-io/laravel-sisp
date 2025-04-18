<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->security();

arch('annotations')
    ->expect('Akira\Sisp')
    ->toHaveMethodsDocumented();
