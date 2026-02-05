<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\URL;

it('retries payment for an existing transaction with signed route', function (): void {
    $t = Transaction::factory()->create([
        'amount' => 123.0,
        'currency' => '132',
        'status' => 'failed',
    ]);

    $url = URL::signedRoute('sisp.retry-payment', ['transaction' => $t->id]);

    $this->post($url)->assertOk();
});

it('rejects unsigned retry requests', function (): void {
    $t = Transaction::factory()->create([
        'amount' => 123.0,
        'currency' => '132',
        'status' => 'failed',
    ]);

    // Construct URL without signature
    $url = route('sisp.retry-payment', ['transaction' => $t->id]);

    $this->post($url)->assertStatus(403);
});
