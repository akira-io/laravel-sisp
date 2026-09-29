<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RecordRefundAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Refund;
use Akira\Sisp\Models\Transaction;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('sums the refunds recorded in the table', function (): void {
    $transaction = Transaction::factory()->create(['amount' => 1000.0]);

    Refund::query()->create([
        'transaction_id' => $transaction->id,
        'amount' => 250.0,
        'reason' => 'partial',
        'request' => ['amount' => 250.0],
    ]);

    expect($transaction->refresh()->refunds)->toHaveCount(1)
        ->and((float) $transaction->refunds->sum('amount'))->toBe(250.0);
});

it('falls back to the legacy payload when the table has no rows', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 1000.0,
        'payload' => [
            'refunds' => [
                ['amount' => 400.0, 'reason' => 'legacy', 'request' => []],
            ],
        ],
    ]);

    $refundable = resolve(RecordRefundAction::class)->refundableAmount($transaction);

    expect($refundable)->toBe(600.0);
});

it('belongs to the transaction it refunds', function (): void {
    $transaction = Transaction::factory()->create(['amount' => 1000.0]);

    $refund = Refund::query()->create([
        'transaction_id' => $transaction->id,
        'amount' => 100.0,
        'reason' => 'partial',
        'request' => [],
    ]);

    expect($refund->transaction->id)->toBe($transaction->id);
});

it('prefers the table over the legacy payload once a row exists', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 1000.0,
        'payload' => [
            'refunds' => [
                ['amount' => 400.0, 'reason' => 'legacy', 'request' => []],
            ],
        ],
    ]);

    Refund::query()->create([
        'transaction_id' => $transaction->id,
        'amount' => 400.0,
        'reason' => 'migrated',
        'request' => [],
    ]);

    expect(resolve(RecordRefundAction::class)->refundableAmount($transaction->refresh()))
        ->toBe(600.0);
});

it('rejects a refund that would exceed the balance after the legacy history is backfilled', function (): void {
    $transaction = Transaction::factory()->create([
        'amount' => 100.0,
        'status' => TransactionStatus::completed->value,
        'transaction_id' => '123',
        'response_code' => '5',
        'payload' => [
            'refunds' => [
                ['amount' => 75.0, 'reason' => 'legacy', 'request' => []],
            ],
        ],
    ]);

    $action = resolve(RecordRefundAction::class);

    expect($action->refundableAmount($transaction))->toBe(25.0);

    $action->handle($transaction, 5.0);

    expect($action->refundableAmount($transaction->refresh()))->toBe(20.0)
        ->and(Refund::query()->where('transaction_id', $transaction->id)->count())->toBe(2);

    expect(fn (): Transaction => $action->handle($transaction, 95.0))
        ->toThrow(LogicException::class);
});

it('does not double count a transaction whose history was already migrated', function (): void {
    $transaction = Transaction::factory()->create([
        'amount' => 100.0,
        'status' => TransactionStatus::completed->value,
        'transaction_id' => '123',
        'response_code' => '5',
        'payload' => [
            'refunds' => [
                ['amount' => 75.0, 'reason' => 'legacy', 'request' => []],
            ],
        ],
    ]);

    Refund::query()->create([
        'transaction_id' => $transaction->id,
        'amount' => 75.0,
        'reason' => 'migrated',
        'request' => [],
    ]);

    $action = resolve(RecordRefundAction::class);

    $action->handle($transaction->refresh(), 5.0);

    expect(Refund::query()->where('transaction_id', $transaction->id)->count())->toBe(2)
        ->and($action->refundableAmount($transaction->refresh()))->toBe(20.0);
});

it('round trips an amount with three decimal places through the table', function (): void {
    $transaction = Transaction::factory()->create([
        'amount' => 100.0,
        'status' => TransactionStatus::completed->value,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    resolve(RecordRefundAction::class)->handle($transaction, 8.035);

    $refund = Refund::query()->where('transaction_id', $transaction->id)->sole();

    expect($refund->amount_thousandths)->toBe(8035)
        ->and(resolve(RecordRefundAction::class)->refundableAmount($transaction->refresh()))
        ->toBe(91.965);
});

it('keeps the refund request out of plaintext in the database', function (): void {
    $transaction = Transaction::factory()->create([
        'amount' => 100.0,
        'status' => TransactionStatus::completed->value,
        'transaction_id' => '123',
        'response_code' => '5',
        'merchant_ref' => 'MREF-PLAINTEXT-PROBE',
    ]);

    resolve(RecordRefundAction::class)->handle($transaction, 10.0);

    $raw = DB::table(config('sisp.tables.refunds'))
        ->where('transaction_id', $transaction->id)
        ->value('request');

    expect($raw)->toBeString()
        ->and($raw)->not->toContain('MREF-PLAINTEXT-PROBE')
        ->and($raw)->not->toContain('fingerprint');

    $refund = Refund::query()->where('transaction_id', $transaction->id)->sole();

    expect($refund->request)->toBeArray()
        ->and($refund->request['merchantRef'])->toBe('MREF-PLAINTEXT-PROBE');
});

it('copies the legacy history into encrypted rows when the migration runs', function (): void {
    $transaction = Transaction::factory()->create([
        'amount' => 100.0,
        'status' => TransactionStatus::completed->value,
        'payload' => [
            'refunds' => [
                ['amount' => 8.035, 'reason' => 'legacy', 'request' => ['merchantRef' => 'MREF-LEGACY-9']],
                'not-an-array',
            ],
        ],
    ]);

    $refundsTable = config('sisp.tables.refunds');

    Schema::dropIfExists($refundsTable);

    $migration = require dirname(__DIR__, 2).'/database/migrations/create_sisp_refunds_table.php';
    $migration->up();

    $refund = Refund::query()->where('transaction_id', $transaction->id)->sole();

    expect(Refund::query()->count())->toBe(1)
        ->and($refund->amount_thousandths)->toBe(8035)
        ->and($refund->reason)->toBe('legacy')
        ->and($refund->request['merchantRef'])->toBe('MREF-LEGACY-9');

    $raw = DB::table($refundsTable)->where('transaction_id', $transaction->id)->value('request');

    expect($raw)->toBeString()->and($raw)->not->toContain('MREF-LEGACY-9');

    expect(resolve(RecordRefundAction::class)->refundableAmount($transaction->refresh()))
        ->toBe(91.965);
});

it('does not duplicate rows when the migration copy step runs twice', function (): void {
    $transaction = Transaction::factory()->create([
        'amount' => 100.0,
        'status' => TransactionStatus::completed->value,
        'payload' => [
            'refunds' => [
                ['amount' => 8.035, 'reason' => 'legacy', 'request' => ['merchantRef' => 'MREF-LEGACY-9']],
            ],
        ],
    ]);

    $refundsTable = config('sisp.tables.refunds');

    Schema::dropIfExists($refundsTable);

    $migration = require dirname(__DIR__, 2).'/database/migrations/create_sisp_refunds_table.php';
    $migration->up();
    $migration->up();

    expect(Refund::query()->where('transaction_id', $transaction->id)->count())->toBe(1);

    expect(resolve(RecordRefundAction::class)->refundableAmount($transaction->refresh()))
        ->toBe(91.965);
});

it('refuses to compute a refundable balance from a legacy refunds key it cannot read', function (): void {
    $transaction = Transaction::factory()->create([
        'amount' => 100.0,
        'status' => TransactionStatus::completed->value,
        'payload' => ['refunds' => 'corrupted'],
    ]);

    expect(fn (): float => resolve(RecordRefundAction::class)->refundableAmount($transaction))
        ->toThrow(LogicException::class, 'The stored refund history could not be decoded.');
});

it('refuses to refund a transaction whose payload cannot be decoded and leaves the payload untouched', function (): void {
    $transaction = Transaction::factory()->create([
        'amount' => 100.0,
        'status' => TransactionStatus::completed->value,
    ]);
    $sealed = new Encrypter(Encrypter::generateKey(config('app.cipher')), config('app.cipher'))
        ->encryptString(json_encode(['refunds' => [['amount' => 75.0, 'reason' => 'legacy', 'request' => []]]]));

    DB::table(config('sisp.tables.transactions'))->where('id', $transaction->id)->update(['payload' => $sealed]);

    expect(fn (): Transaction => resolve(RecordRefundAction::class)->handle($transaction->refresh(), 50.0))
        ->toThrow(LogicException::class, 'The stored transaction payload could not be decoded.')
        ->and(DB::table(config('sisp.tables.transactions'))->where('id', $transaction->id)->value('payload'))->toBe($sealed)
        ->and($transaction->refresh()->status)->toBe(TransactionStatus::completed);
});

it('counts the legacy history of a payload stored as a plain JSON string', function (): void {
    $transaction = Transaction::factory()->create([
        'amount' => 100.0,
        'status' => TransactionStatus::completed->value,
    ]);

    DB::table(config('sisp.tables.transactions'))->where('id', $transaction->id)->update([
        'payload' => json_encode(['refunds' => [['amount' => 75.0, 'reason' => 'legacy', 'request' => []]]]),
    ]);

    expect(resolve(RecordRefundAction::class)->refundableAmount($transaction->refresh()))->toBe(25.0);
});

it('keeps the rest of a JSON string payload when a refund is appended to it', function (): void {
    $transaction = Transaction::factory()->create([
        'amount' => 100.0,
        'status' => TransactionStatus::completed->value,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    DB::table(config('sisp.tables.transactions'))->where('id', $transaction->id)->update([
        'payload' => json_encode(['posID' => '90001', 'refunds' => [['amount' => 10.0, 'reason' => 'legacy', 'request' => []]]]),
    ]);

    resolve(RecordRefundAction::class)->handle($transaction->refresh(), 5.0);

    $payload = $transaction->refresh()->payload;

    expect($payload['posID'])->toBe('90001')
        ->and($payload['refunds'])->toHaveCount(2);
});
