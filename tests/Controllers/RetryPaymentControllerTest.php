<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;

it('retries payment and renders form', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'failed',
        'merchant_ref' => 'MR-R',
        'merchant_session' => 'MS-R',
        'amount' => 30.0,
        'currency' => '132',
    ]);

    $response = $this->post(route('sisp.retry-payment'), ['transaction_id' => $t->id]);
    $response->assertStatus(200);
});
