<?php

declare(strict_types=1);

use Akira\Sisp\Actions\Generators\MerchantReferenceGeneratorAction;

it('generates a valid uuid merchant reference', function (): void {
    $gen = resolve(MerchantReferenceGeneratorAction::class);
    $ref = $gen();

    expect($ref)->toBeString()
        ->and(strlen($ref))->toBe(36)
        ->and((bool) preg_match('/^[0-9a-f\-]{36}$/i', $ref))->toBeTrue();
});

