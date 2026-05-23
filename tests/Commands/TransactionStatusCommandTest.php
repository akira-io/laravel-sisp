<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');
});

it('prints the status API response for a merchant reference', function (): void {
    Http::fake([
        '*' => Http::response([
            'result' => true,
            'transactionSuccess' => true,
            'transactionStatusDescription' => 'C-SUCESSO',
            'msg' => 'Approved',
        ]),
    ]);

    $this->artisan('sisp:transaction-status MR-CMD-1')
        ->expectsOutput('Result: success')
        ->expectsOutput('Payment: completed')
        ->expectsOutput('Description: C-SUCESSO')
        ->expectsOutput('Message: Approved')
        ->assertSuccessful();
});

it('updates the local transaction only when requested', function (): void {
    Http::fake([
        '*' => Http::response([
            'result' => true,
            'transactionSuccess' => false,
            'transactionStatusDescription' => 'E-ERRO',
            'msg' => 'Declined',
        ]),
    ]);

    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-CMD-2',
        'status' => 'pending',
        'merchant_response' => null,
    ]);

    $this->artisan("sisp:transaction-status --transaction={$transaction->id} --update")
        ->expectsOutput('Local transaction updated.')
        ->assertSuccessful();

    expect($transaction->refresh()->status->value)->toBe('failed')
        ->and($transaction->merchant_response)->toBe('E-ERRO');
});
