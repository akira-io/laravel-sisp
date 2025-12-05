<?php

declare(strict_types=1);

use Akira\Sisp\Actions\Generators\MerchantSessionGeneratorAction;

it('generates a random 32 length merchant session', function (): void {
    $gen = resolve(MerchantSessionGeneratorAction::class);
    $session = $gen();

    expect($session)->toBeString()
        ->and(mb_strlen($session))->toBe(32);
});
