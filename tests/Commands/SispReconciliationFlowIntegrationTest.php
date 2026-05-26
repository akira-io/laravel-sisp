<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Http;

it('reconciles old pending transactions to completed and failed through the command flow', function (): void {
    config([
        'sisp.transaction_status.portal_id' => 'portal',
        'sisp.transaction_status.portal_password' => 'secret',
        'sisp.transaction_status.reconciliation_enabled' => true,
        'sisp.transaction_status.reconcile_after_minutes' => 5,
        'sisp.transaction_status.reconcile_limit' => 10,
    ]);

    Http::fakeSequence()
        ->push([
            'result' => true,
            'transactionSuccess' => true,
            'transactionStatusDescription' => 'C-SUCESSO',
            'msg' => 'Approved',
        ])
        ->push([
            'result' => true,
            'transactionSuccess' => false,
            'transactionStatusDescription' => 'E-ERRO',
            'msg' => 'Declined',
        ]);

    $completed = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => null,
        'created_at' => now()->subMinutes(6),
    ]);
    $failed = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => null,
        'created_at' => now()->subMinutes(6),
    ]);

    $this->artisan('sisp:reconcile-pending')
        ->expectsOutput('Reconciled 2 of 2 pending SISP transactions.')
        ->assertSuccessful();

    expect($completed->refresh()->status->value)->toBe('completed')
        ->and($failed->refresh()->status->value)->toBe('failed');
});
