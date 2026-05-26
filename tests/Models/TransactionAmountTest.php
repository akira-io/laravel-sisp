<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;

it('falls back to amount when amount cents is missing', function (): void {
    $transaction = new Transaction();
    $transaction->setRawAttributes(['amount' => 8.03], true);

    expect($transaction->amount_cents)->toBe(803);
});

it('prefers stored amount cents when present', function (): void {
    $transaction = new Transaction();
    $transaction->setRawAttributes(['amount' => 8.03, 'amount_cents' => 804], true);

    expect($transaction->amount_cents)->toBe(804);
});
