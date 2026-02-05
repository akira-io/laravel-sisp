<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\URL;

it('retries payment and renders form', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'failed',
        'merchant_ref' => 'MR-R',
        'merchant_session' => 'MS-R',
        'amount' => 30.0,
        'currency' => '132',
    ]);

    $url = URL::signedRoute('sisp.retry-payment', ['transaction' => $t->id]);

    $response = $this->post($url);
    $response->assertStatus(200);
});
