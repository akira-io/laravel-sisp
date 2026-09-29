<?php

declare(strict_types=1);

use Akira\Sisp\Actions\ReconcileTransactionStatusAction;
use Akira\Sisp\Models\Transaction;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
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

it('keeps the rest of a payload stored as a plain JSON string', function (): void {
    Http::fake([
        '*' => Http::response([
            'result' => true,
            'transactionSuccess' => true,
            'transactionStatusDescription' => 'C-SUCESSO',
            'msg' => 'Approved',
        ]),
    ]);

    $transaction = Transaction::factory()->create(['status' => 'pending']);

    DB::table(config('sisp.tables.transactions'))->where('id', $transaction->id)->update([
        'payload' => json_encode(['posID' => '90001', 'refunds' => [['amount' => 12.5]]]),
    ]);

    $updated = resolve(ReconcileTransactionStatusAction::class)->handle($transaction->refresh());

    expect($updated->payload['posID'])->toBe('90001')
        ->and($updated->payload['refunds'])->toBe([['amount' => 12.5]])
        ->and($updated->payload['transaction_status_response']['transactionSuccess'])->toBeTrue();
});

it('reconciles the status but leaves a payload it cannot decode untouched', function (): void {
    Http::fake([
        '*' => Http::response([
            'result' => true,
            'transactionSuccess' => true,
            'transactionStatusDescription' => 'C-SUCESSO',
            'msg' => 'Approved',
        ]),
    ]);

    $transaction = Transaction::factory()->create(['status' => 'pending']);
    $sealed = new Encrypter(Encrypter::generateKey(config('app.cipher')), config('app.cipher'))
        ->encryptString(json_encode(['posID' => '90001']));

    DB::table(config('sisp.tables.transactions'))->where('id', $transaction->id)->update(['payload' => $sealed]);

    $updated = resolve(ReconcileTransactionStatusAction::class)->handle($transaction->refresh());

    expect($updated->status->value)->toBe('completed')
        ->and(DB::table(config('sisp.tables.transactions'))->where('id', $transaction->id)->value('payload'))->toBe($sealed);
});
