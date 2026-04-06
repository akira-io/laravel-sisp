<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;

it('cancels a pending transaction and redirects', function (): void {
    Transaction::factory()->create([
        'status' => 'pending',
        'merchant_ref' => 'MR-C',
        'merchant_session' => 'MS-C',
    ]);

    $response = $this->get(route('sisp.cancel', [
        'merchantRef' => 'MR-C',
        'reason' => 'user_cancelled',
    ]));

    $response->assertRedirect();
});

it('returns 404 when merchantRef does not match any transaction', function (): void {
    $response = $this->get(route('sisp.cancel', [
        'merchantRef' => 'NONEXISTENT',
        'reason' => 'user_cancelled',
    ]));

    $response->assertNotFound();
});

it('returns validation error when merchantRef is missing', function (): void {
    $response = $this->get(route('sisp.cancel'));

    $response->assertRedirect();
    $response->assertSessionHasErrors(['merchantRef']);
});

it('handles non-cancellable transaction and flashes error', function (): void {
    Transaction::factory()->create([
        'status' => 'completed',
        'merchant_ref' => 'MR-C2',
        'merchant_session' => 'MS-C2',
    ]);

    $response = $this->get(route('sisp.cancel', [
        'merchantRef' => 'MR-C2',
        'reason' => 'user_cancelled',
    ]));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});
