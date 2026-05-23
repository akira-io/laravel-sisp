<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');
});

it('does not run reconciliation when the feature flag is disabled', function (): void {
    config()->set('sisp.transaction_status.reconciliation_enabled', false);

    Http::fake();

    $this->artisan('sisp:reconcile-pending')
        ->expectsOutput('SISP transaction reconciliation is disabled.')
        ->assertSuccessful();

    Http::assertNothingSent();
});

it('reconciles old indeterminate pending transactions when enabled', function (): void {
    config()->set('sisp.transaction_status.reconciliation_enabled', true);
    config()->set('sisp.transaction_status.reconcile_after_minutes', 5);
    config()->set('sisp.transaction_status.reconcile_limit', 10);

    Http::fake([
        '*' => Http::response([
            'result' => true,
            'transactionSuccess' => false,
            'transactionStatusDescription' => 'E-ERRO',
            'msg' => 'Declined',
        ]),
    ]);

    $oldPending = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => null,
        'created_at' => now()->subMinutes(6),
    ]);
    $freshPending = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => null,
        'created_at' => now()->subMinutes(2),
    ]);

    $this->artisan('sisp:reconcile-pending')
        ->expectsOutput('Reconciled 1 of 1 pending SISP transactions.')
        ->assertSuccessful();

    expect($oldPending->refresh()->status->value)->toBe('failed')
        ->and($freshPending->refresh()->status->value)->toBe('pending');
});
