<?php

declare(strict_types=1);

use Akira\Sisp\Actions\ReconcileTransactionStatusAction;
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');
});

it('updates pending transactions when SISP returns a definitive successful payment', function (): void {
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
        'payload' => ['existing' => true],
        'merchant_response' => null,
    ]);

    $updated = resolve(ReconcileTransactionStatusAction::class)->handle($transaction);

    expect($updated->status->value)->toBe('completed')
        ->and($updated->merchant_response)->toBe('C-SUCESSO')
        ->and($updated->payload['existing'])->toBeTrue()
        ->and($updated->payload['transaction_status_response']['transactionSuccess'])->toBeTrue();
});

it('updates pending transactions when SISP returns a definitive failed payment', function (): void {
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
        'merchant_response' => null,
    ]);

    $updated = resolve(ReconcileTransactionStatusAction::class)->handle($transaction);

    expect($updated->status->value)->toBe('failed')
        ->and($updated->merchant_response)->toBe('E-ERRO');
});

it('keeps pending transactions unchanged when the status API query fails', function (): void {
    Http::fake([
        '*' => Http::response(['msg' => 'Forbidden'], 403),
    ]);

    $transaction = Transaction::factory()->create([
        'status' => 'pending',
        'merchant_response' => null,
    ]);

    $updated = resolve(ReconcileTransactionStatusAction::class)->handle($transaction);

    expect($updated->status->value)->toBe('pending')
        ->and($updated->merchant_response)->toBeNull();
});
