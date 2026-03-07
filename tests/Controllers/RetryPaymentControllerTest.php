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

it('updates merchant_session on transaction so callback can find it', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'failed',
        'merchant_ref' => 'MR-R2',
        'merchant_session' => 'MS-OLD',
        'amount' => 50.0,
        'currency' => '132',
    ]);

    $this->post(route('sisp.retry-payment'), ['transaction_id' => $t->id])
        ->assertStatus(200);

    $t->refresh();

    expect($t->merchant_session)
        ->not->toBe('MS-OLD')
        ->not->toBeEmpty();
});
