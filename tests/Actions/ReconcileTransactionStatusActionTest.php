<?php

declare(strict_types=1);

use Akira\Sisp\Actions\ReconcileTransactionStatusAction;
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');
});

it('marks pending transactions completed when SISP reports success', function (): void {
    Http::fake([
        '*' => Http::response([
            'result' => true,
            'transactionSuccess' => true,
            'transactionStatusDescription' => 'C-SUCESSO',
            'msg' => 'Approved',
        ]),
    ]);

    $transaction = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => null,
        'merchant_response' => null,
    ]);

    $updated = resolve(ReconcileTransactionStatusAction::class)->handle($transaction);

    expect($updated->status->value)->toBe('completed')
        ->and($updated->message_type)->toBe('transaction_status_success')
        ->and($updated->merchant_response)->toBe('C-SUCESSO')
        ->and($updated->payload['transaction_status_response']['transactionSuccess'])->toBeTrue();
});

it('marks pending transactions failed when SISP reports a completed status check with failed payment', function (): void {
    Http::fake([
        '*' => Http::response([
            'result' => true,
            'transactionSuccess' => false,
            'transactionStatusDescription' => 'E-ERRO',
            'msg' => 'Declined',
        ]),
    ]);

    $transaction = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => null,
        'merchant_response' => null,
    ]);

    $updated = resolve(ReconcileTransactionStatusAction::class)->handle($transaction);

    expect($updated->status->value)->toBe('failed')
        ->and($updated->message_type)->toBe('transaction_status_failed')
        ->and($updated->merchant_response)->toBe('E-ERRO');
});

it('keeps pending transactions unchanged when the status API call is unsuccessful', function (): void {
    Http::fake([
        '*' => Http::response([
            'result' => false,
            'transactionSuccess' => false,
            'transactionStatusDescription' => '',
            'msg' => 'Invalid credentials',
        ]),
    ]);

    $transaction = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => null,
        'merchant_response' => null,
    ]);

    $updated = resolve(ReconcileTransactionStatusAction::class)->handle($transaction);

    expect($updated->status->value)->toBe('pending')
        ->and($updated->message_type)->toBeNull()
        ->and($updated->merchant_response)->toBeNull();
});
