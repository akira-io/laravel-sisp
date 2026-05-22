<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\URL;

it('cancels a pending transaction from a signed request and redirects', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'pending',
        'merchant_ref' => 'MR-C',
        'merchant_session' => 'MS-C',
    ]);

    $this->get(URL::signedRoute('sisp.cancel', [
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
        ->get(URL::signedRoute('sisp.cancel', [
            'reason' => 'user_cancelled',
            'merchantRef' => 'MR-C2',
        ]))
        ->assertRedirect('/checkout')
        ->assertSessionHas('error');
});

it('cancels a pending transaction resolved by transaction_id from a signed request', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'pending',
        'merchant_ref' => 'MR-C3',
        'merchant_session' => 'MS-C3',
        'transaction_id' => 'TXN-EXT-001',
    ]);

    $this->get(URL::signedRoute('sisp.cancel', [
        'reason' => 'user_cancelled',
        'transaction_id' => 'TXN-EXT-001',
    ]))
        ->assertRedirect(route('sisp.callback', ['ref' => 'MR-C3']));

    expect($t->refresh()->status->value)->toBe('cancelled');
});

it('handles missing transaction identifier and flashes error', function (): void {
    $this->withHeader('referer', '/checkout')
        ->get(URL::signedRoute('sisp.cancel'))
        ->assertRedirect('/checkout')
        ->assertSessionHas('error', __('laravel-sisp::messages.validation.transaction_not_found'));
});

it('rejects unsigned merchant reference cancellation attempts', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'pending',
        'merchant_ref' => 'MR-UNSIGNED',
        'merchant_session' => 'MS-UNSIGNED',
    ]);

    $this->get(route('sisp.cancel', [
        'reason' => 'user_cancelled',
        'merchantRef' => 'MR-UNSIGNED',
    ]))->assertForbidden();

    expect($t->refresh()->status->value)->toBe('pending');
});

it('rejects unsigned transaction id cancellation attempts', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'pending',
        'merchant_ref' => 'MR-TXN-UNSIGNED',
        'merchant_session' => 'MS-TXN-UNSIGNED',
        'transaction_id' => 'TXN-UNSIGNED-001',
    ]);

    $this->get(route('sisp.cancel', [
        'reason' => 'user_cancelled',
        'transaction_id' => 'TXN-UNSIGNED-001',
    ]))->assertForbidden();

    expect($t->refresh()->status->value)->toBe('pending');
});
