<?php

declare(strict_types=1);

use Akira\Sisp\Enums\SuccessMessageType;
use Akira\Sisp\Enums\TransactionCode;

it('success message types have labels', function (): void {
    foreach (SuccessMessageType::cases() as $case) {
        expect($case->value)->not->toBe('')
            ->and($case->label())->not->toBe('');
    }
});

it('transaction codes have labels', function (): void {
    foreach (TransactionCode::cases() as $case) {
        expect($case->value)->not->toBe('')
            ->and($case->label())->not->toBe('');
    }
});
