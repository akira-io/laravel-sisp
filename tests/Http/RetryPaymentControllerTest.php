<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;

it('retries payment for an existing transaction', function (): void {
    $t = Transaction::factory()->create([
        'amount' => 123.0,
        'currency' => '132',
        'status' => 'failed',
    ]);

    $this->post(route('sisp.retry-payment'), [
        'transaction_id' => $t->id,
    ])->assertOk();
});

