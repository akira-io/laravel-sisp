<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\URL;

it('retries payment for an existing transaction', function (): void {
    $t = Transaction::factory()->create([
        'amount' => 123.0,
        'currency' => '132',
        'status' => 'failed',
    ]);

    $this->post(URL::signedRoute('sisp.retry-payment', $t))->assertOk();
});
