<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;

it('cancels a pending transaction and redirects', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'pending',
        'merchant_ref' => 'MR-C',
        'merchant_session' => 'MS-C',
    ]);

    $this->get(route('sisp.cancel', [
        'reason' => 'user_cancelled',
        'merchantRef' => 'MR-C',
    ]))
        ->assertRedirect(route('sisp.callback', ['ref' => 'MR-C']));

    expect($t->refresh()->status->value)->toBe('cancelled');
});

it('handles non-cancellable transaction and flashes error', function (): void {
    Transaction::factory()->create([
        'status' => 'completed',
        'merchant_ref' => 'MR-C2',
        'merchant_session' => 'MS-C2',
    ]);

    $this->withHeader('referer', '/checkout')
        ->get(route('sisp.cancel', [
            'reason' => 'user_cancelled',
            'merchantRef' => 'MR-C2',
        ]))
        ->assertRedirect('/checkout')
        ->assertSessionHas('error');
});

it('cancels a pending transaction resolved by transaction_id', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'pending',
        'merchant_ref' => 'MR-C3',
        'merchant_session' => 'MS-C3',
        'transaction_id' => 'TXN-EXT-001',
    ]);

    $this->get(route('sisp.cancel', [
        'reason' => 'user_cancelled',
        'transaction_id' => 'TXN-EXT-001',
    ]))
        ->assertRedirect(route('sisp.callback', ['ref' => 'MR-C3']));

    expect($t->refresh()->status->value)->toBe('cancelled');
});

it('handles missing transaction identifier and flashes error', function (): void {
    $this->withHeader('referer', '/checkout')
        ->get(route('sisp.cancel'))
        ->assertRedirect('/checkout')
        ->assertSessionHas('error', 'Transaction not found');
});
