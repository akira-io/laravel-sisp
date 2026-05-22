<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;

it('retries payment for an existing transaction', function (): void {
    $t = Transaction::factory()->create([
        'amount' => 123.0,
        'currency' => '132',
        'status' => 'failed',
        'merchant_session' => 'old-session',
        'transaction_code' => '1',
    ]);

    $this->post(route('sisp.retry-payment'), [
        'transaction_id' => $t->id,
    ])->assertOk();

    $t->refresh();

    expect($t->merchant_session)->not->toBe('old-session')
        ->and($t->merchant_session)->not->toBe('')
        ->and($t->amount)->toBe(123.0)
        ->and($t->currency)->toBe('132')
        ->and($t->status->value)->toBe('failed');
});
