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

it('returns validation error without triggering after callback when transaction does not exist', function (): void {
    $this->post(route('sisp.retry-payment'), ['transaction_id' => 999999])
        ->assertSessionHasErrors(['transaction_id']);
});

it('rejects retry when 3DS is enabled and required customer data is missing', function (): void {
    config([
        'sisp.allow_retry' => true,
        'sisp.is_3dsec' => '1',
    ]);

    $t = Transaction::factory()->failed()->create([
        'customer_email' => null,
        'customer_country' => null,
        'customer_city' => null,
        'customer_address' => null,
        'customer_postal_code' => null,
    ]);

    $this->from('/sisp/callback?ref='.$t->merchant_ref)
        ->post(route('sisp.retry-payment'), ['transaction_id' => $t->id])
        ->assertSessionHasErrors(['transaction_id']);
});
