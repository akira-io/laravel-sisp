<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\URL;

it('retries payment and renders form', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'failed',
        'merchant_ref' => 'MR-R',
        'merchant_session' => 'MS-R',
        'amount' => 30.0,
        'currency' => '132',
    ]);

    $response = $this->post(
        URL::temporarySignedRoute('sisp.retry-payment', now()->addMinutes(15), ['transaction_id' => $t->id])
    );
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

    $this->post(
        URL::temporarySignedRoute('sisp.retry-payment', now()->addMinutes(15), ['transaction_id' => $t->id])
    )
        ->assertStatus(200);

    $t->refresh();

    expect($t->merchant_session)
        ->not->toBe('MS-OLD')
        ->not->toBeEmpty();
});

it('denies unsigned retry requests', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'failed',
        'merchant_ref' => 'MR-UNSIGNED',
        'merchant_session' => 'MS-UNSIGNED',
    ]);

    $this->post(route('sisp.retry-payment'), ['transaction_id' => $t->id])
        ->assertForbidden();
});

it('denies retry for authenticated users who do not own the transaction', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'failed',
        'customer_email' => 'owner@example.com',
    ]);

    $this->actingAs(new GenericUser([
        'id' => 999,
        'email' => 'attacker@example.com',
    ]));

    $this->post(
        URL::temporarySignedRoute('sisp.retry-payment', now()->addMinutes(15), ['transaction_id' => $t->id])
    )->assertForbidden();
});

it('allows retry for authenticated users who own the transaction', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'failed',
        'customer_email' => 'owner@example.com',
    ]);

    $this->actingAs(new GenericUser([
        'id' => 123,
        'email' => 'owner@example.com',
    ]));

    $this->post(
        URL::temporarySignedRoute('sisp.retry-payment', now()->addMinutes(15), ['transaction_id' => $t->id])
    )->assertOk();
});
