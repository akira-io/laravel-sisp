<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RefundTransactionAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Refund;
use Akira\Sisp\Models\Transaction;
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
        'amount' => 1000.0,
        'payload' => [
            'refunds' => [
                ['amount' => 400.0, 'reason' => 'legacy', 'request' => []],
            ],
        ],
    ]);

    $refundable = resolve(RefundTransactionAction::class)->refundableAmount($transaction);

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

    expect(resolve(RefundTransactionAction::class)->refundableAmount($transaction->refresh()))
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

    $action = resolve(RefundTransactionAction::class);

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

    $action = resolve(RefundTransactionAction::class);

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

    resolve(RefundTransactionAction::class)->handle($transaction, 8.035);

    $refund = Refund::query()->where('transaction_id', $transaction->id)->sole();

    expect($refund->amount_thousandths)->toBe(8035)
        ->and(resolve(RefundTransactionAction::class)->refundableAmount($transaction->refresh()))
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

    resolve(RefundTransactionAction::class)->handle($transaction, 10.0);

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

    expect(resolve(RefundTransactionAction::class)->refundableAmount($transaction->refresh()))
        ->toBe(91.965);
});

it('ignores a legacy refunds key that is not a list', function (): void {
    $transaction = Transaction::factory()->create([
        'amount' => 100.0,
        'status' => TransactionStatus::completed->value,
        'payload' => ['refunds' => 'corrupted'],
    ]);

    expect(resolve(RefundTransactionAction::class)->refundableAmount($transaction))->toBe(100.0);
});
