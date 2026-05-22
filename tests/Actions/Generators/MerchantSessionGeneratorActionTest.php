<?php

declare(strict_types=1);

use Akira\Sisp\Actions\Generators\MerchantSessionGeneratorAction;
use Illuminate\Support\Facades\Date;

it('generates a SISP merchant session with the recommended timestamp format', function (): void {
    Date::setTestNow('2026-05-23 10:11:12');

    try {
        $gen = resolve(MerchantSessionGeneratorAction::class);
        $session = $gen();

        expect($session)->toBe('S20260523101112');
    } finally {
        Date::setTestNow();
    }
});
