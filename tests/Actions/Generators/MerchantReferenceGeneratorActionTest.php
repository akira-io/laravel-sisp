<?php

declare(strict_types=1);

use Akira\Sisp\Actions\Generators\MerchantReferenceGeneratorAction;
use Illuminate\Support\Facades\Date;

it('generates a SISP merchant reference with the recommended timestamp format', function (): void {
    Date::setTestNow('2026-05-23 10:11:12');

    try {
        $gen = resolve(MerchantReferenceGeneratorAction::class);
        $ref = $gen();

        expect($ref)->toStartWith('R20260523101112')
            ->and($ref)->toMatch('/^R\\d{14}[A-Z0-9]{10}$/')
            ->and($gen())->not->toBe($ref);
    } finally {
        Date::setTestNow();
    }
});
