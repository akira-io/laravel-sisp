<?php

declare(strict_types=1);

use Akira\Sisp\Actions\CancelTransactionAction;
use Akira\Sisp\Actions\ReconcileTransactionStatusAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionLog;
use Illuminate\Support\Facades\Http;

it('records changed transaction attributes with previous and new values', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::pending->value,
        'merchant_response' => null,
    ]);

    $transaction->update([
        'status' => TransactionStatus::completed->value,
        'merchant_response' => 'C-SUCESSO',
    ]);

    $log = $transaction->logs()->sole();

    expect($log)->toBeInstanceOf(TransactionLog::class)
        ->and($log->source)->toBe('model')
        ->and($log->changed_attributes)->toBe(['status', 'merchant_response'])
        ->and($log->old_values)->toMatchArray([
            'status' => 'pending',
            'merchant_response' => null,
        ])
        ->and($log->new_values)->toMatchArray([
            'status' => 'completed',
            'merchant_response' => 'C-SUCESSO',
        ]);
});

it('does not record timestamp-only transaction updates', function (): void {
    $transaction = Transaction::factory()->create();

    $transaction->touch();

    expect($transaction->logs()->count())->toBe(0);
});

it('records payload changes as decrypted arrays', function (): void {
    $transaction = Transaction::factory()->create([
        'payload' => ['attempt' => 1],
    ]);

    $transaction->update([
        'payload' => ['attempt' => 2, 'status' => ['approved' => true]],
    ]);

    $log = $transaction->logs()->sole();

    expect($log->changed_attributes)->toBe(['payload'])
        ->and($log->old_values['payload'])->toBe(['attempt' => 1])
        ->and($log->new_values['payload'])->toBe([
            'attempt' => 2,
            'status' => ['approved' => true],
        ]);
});

it('uses package flow source for cancellation updates', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::pending->value,
    ]);

    resolve(CancelTransactionAction::class)->handle($transaction, 'user_cancelled');

    $log = $transaction->logs()->sole();

    expect($log->source)->toBe('cancel')
        ->and($log->new_values['status'])->toBe('cancelled')
        ->and($log->new_values['merchant_response'])->toBe('user_cancelled');
});

it('uses package flow source for reconciliation updates', function (): void {
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');

    Http::fake([
        '*' => Http::response([
            'result' => true,
            'transactionSuccess' => true,
            'transactionStatusDescription' => 'C-SUCESSO',
            'msg' => 'Approved',
        ]),
    ]);

    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::pending->value,
        'payload' => ['existing' => true],
    ]);

    resolve(ReconcileTransactionStatusAction::class)->handle($transaction);

    $log = $transaction->logs()->sole();

    expect($log->source)->toBe('reconciliation')
        ->and($log->new_values['status'])->toBe('completed')
        ->and($log->new_values['payload']['transaction_status_response']['transactionSuccess'])->toBeTrue();
});
