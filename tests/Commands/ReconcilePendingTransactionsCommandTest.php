<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Http;

it('reconciles pending transactions older than the configured threshold', function (): void {
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');
    config()->set('sisp.transaction_status.indeterminate_after_minutes', 5);

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
