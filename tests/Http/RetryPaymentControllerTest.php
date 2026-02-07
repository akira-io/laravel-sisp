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

    $signedUrl = URL::signedRoute('sisp.retry-payment', ['transaction' => $t]);

    $this->get($signedUrl)->assertOk();
});

it('cannot retry payment without valid signature', function (): void {
    $t = Transaction::factory()->create([
        'amount' => 123.0,
        'currency' => '132',
        'status' => 'failed',
    ]);

    // Attempt to access without signature or with invalid one
    $this->get(route('sisp.retry-payment', ['transaction' => $t]))
        ->assertForbidden();
});
