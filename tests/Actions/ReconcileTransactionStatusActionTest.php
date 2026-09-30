<?php

declare(strict_types=1);

use Akira\Sisp\Actions\ReconcileTransactionStatusAction;
use Akira\Sisp\Events\PaymentCompleted;
use Akira\Sisp\Events\PaymentFailed;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\TransactionStatusResponse;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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

it('does not overwrite a payment that completed while the gateway was being queried', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending', 'merchant_response' => null]);
    $invoice = $transaction->invoice()->create([
        'invoice_number' => 'INV-RACE-1',
        'invoice_date' => now(),
        'status' => 'paid',
    ]);

    // The real callback settles the row after the reconciler loaded it as pending.
    DB::table(config('sisp.tables.transactions'))->where('id', $transaction->id)->update([
        'status' => 'completed',
        'merchant_response' => 'C',
    ]);

    $stale = TransactionStatusResponse::from([
        'result' => true,
        'transactionSuccess' => false,
        'transactionStatusDescription' => 'NOT SETTLED',
    ]);

    $result = resolve(ReconcileTransactionStatusAction::class)->applyResponse($transaction, $stale);

    expect($result->refresh()->status->value)->toBe('completed')
        ->and($result->merchant_response)->toBe('C')
        ->and($invoice->refresh()->status->value)->toBe('paid');
});

it('applies the gateway answer when the row is still pending under the lock', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending']);

    $answer = TransactionStatusResponse::from([
        'result' => true,
        'transactionSuccess' => false,
        'transactionStatusDescription' => 'DECLINED',
    ]);

    $result = resolve(ReconcileTransactionStatusAction::class)->applyResponse($transaction, $answer);

    expect($result->status->value)->toBe('failed')
        ->and($result->merchant_response)->toBe('DECLINED');
});

it('dispatches PaymentCompleted when reconciliation settles a payment as completed', function (): void {
    Event::fake([PaymentCompleted::class, PaymentFailed::class]);
    Http::fake(['*' => Http::response(['result' => true, 'transactionSuccess' => true, 'transactionStatusDescription' => 'C-SUCESSO', 'msg' => 'Approved'])]);

    $transaction = Transaction::factory()->create(['status' => 'pending', 'merchant_ref' => 'MR-RECONCILED', 'transaction_id' => 'TID-9']);

    resolve(ReconcileTransactionStatusAction::class)->handle($transaction);

    Event::assertDispatched(PaymentCompleted::class, function (PaymentCompleted $event): bool {
        return $event->transaction->status->value === 'completed'
            && $event->payload->merchantRef === 'MR-RECONCILED'
            && (string) $event->payload->transactionID === 'TID-9'
            && $event->payload->merchantResponse === 'C-SUCESSO'
            && $event->payload->raw['transactionSuccess'] === true;
    });
    Event::assertNotDispatched(PaymentFailed::class);
});

it('dispatches PaymentFailed when reconciliation settles a payment as failed', function (): void {
    Event::fake([PaymentCompleted::class, PaymentFailed::class]);
    Http::fake(['*' => Http::response(['result' => true, 'transactionSuccess' => false, 'transactionStatusDescription' => 'E-ERRO', 'msg' => 'Declined'])]);

    $transaction = Transaction::factory()->create(['status' => 'pending']);

    resolve(ReconcileTransactionStatusAction::class)->handle($transaction);

    Event::assertDispatched(PaymentFailed::class, fn (PaymentFailed $event): bool => $event->transaction->status->value === 'failed' && $event->payload->merchantResponse === 'E-ERRO');
    Event::assertNotDispatched(PaymentCompleted::class);
});

it('does not dispatch an event when a payment is reconciled a second time', function (): void {
    Event::fake([PaymentCompleted::class, PaymentFailed::class]);
    Http::fake(['*' => Http::response(['result' => true, 'transactionSuccess' => true, 'transactionStatusDescription' => 'C-SUCESSO', 'msg' => 'Approved'])]);

    $transaction = Transaction::factory()->create(['status' => 'pending']);
    $action = resolve(ReconcileTransactionStatusAction::class);

    $action->handle($transaction);
    $action->handle($transaction->refresh());

    Event::assertDispatchedTimes(PaymentCompleted::class, 1);
});

it('does not dispatch an event for a row the callback settled while the gateway was being queried', function (): void {
    Event::fake([PaymentCompleted::class, PaymentFailed::class]);

    $settled = Transaction::factory()->create(['status' => 'pending']);
    DB::table(config('sisp.tables.transactions'))->where('id', $settled->id)->update(['status' => 'completed']);

    resolve(ReconcileTransactionStatusAction::class)->applyResponse($settled, TransactionStatusResponse::from(['result' => true, 'transactionSuccess' => false]));

    Event::assertNothingDispatched();
});

it('does not dispatch an event when the status query fails', function (): void {
    Event::fake([PaymentCompleted::class, PaymentFailed::class]);
    Http::fake(['*' => Http::response(['msg' => 'Forbidden'], 403)]);

    resolve(ReconcileTransactionStatusAction::class)->handle(Transaction::factory()->create(['status' => 'pending']));

    Event::assertNothingDispatched();
});
