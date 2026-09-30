<?php

declare(strict_types=1);

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Refund;
use Akira\Sisp\Models\Transaction;
use Illuminate\Encryption\Encrypter;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

function transactionWithRawPayload(string $rawPayload): Transaction
{
    $transaction = Transaction::factory()->create([
        'amount' => 100.0,
        'status' => TransactionStatus::completed->value,
    ]);

    DB::table(config('sisp.tables.transactions'))
        ->where('id', $transaction->id)
        ->update(['payload' => $rawPayload]);

    return $transaction;
}

/**
 * @return array<int, MessageLogged>
 */
function runRefundsMigration(int $times = 1): array
{
    $logged = [];

    Event::listen(MessageLogged::class, function (MessageLogged $message) use (&$logged): void {
        $logged[] = $message;
    });

    Schema::dropIfExists(config('sisp.tables.refunds'));

    $migration = require dirname(__DIR__, 2).'/database/migrations/create_sisp_refunds_table.php';

    for ($run = 0; $run < $times; $run++) {
        $migration->up();
    }

    return $logged;
}

/**
 * @param  array<int, MessageLogged>  $logged
 */
function loggedAt(array $logged, string $level): ?MessageLogged
{
    return collect($logged)->first(fn (MessageLogged $message): bool => $message->level === $level);
}

$legacyHistory = [
    'posID' => '90001',
    'merchantRef' => 'MREF-LEGACY-1',
    'refunds' => [
        ['amount' => 12.5, 'reason' => 'legacy', 'request' => ['merchantRef' => 'MREF-LEGACY-1']],
    ],
];

it('copies the history of a payload stored as a plain JSON string', function () use ($legacyHistory): void {
    $transaction = transactionWithRawPayload(json_encode($legacyHistory));

    $logged = runRefundsMigration();

    $refund = Refund::query()->where('transaction_id', $transaction->id)->sole();

    expect($refund->amount_thousandths)->toBe(12500)
        ->and($refund->reason)->toBe('legacy')
        ->and($refund->request['merchantRef'])->toBe('MREF-LEGACY-1')
        ->and(loggedAt($logged, 'warning'))->toBeNull()
        ->and(loggedAt($logged, 'error'))->toBeNull();
});

it('copies the history of an encrypted payload whose envelope wraps another envelope', function () use ($legacyHistory): void {
    $transaction = transactionWithRawPayload(Crypt::encryptString(Crypt::encryptString(json_encode($legacyHistory))));

    runRefundsMigration();

    expect(Refund::query()->where('transaction_id', $transaction->id)->sole()->amount_thousandths)->toBe(12500);
});

it('copies a refunds list that is itself stored as a JSON string', function () use ($legacyHistory): void {
    $payload = [...$legacyHistory, 'refunds' => json_encode($legacyHistory['refunds'])];
    $transaction = transactionWithRawPayload(Crypt::encryptString(json_encode($payload)));

    runRefundsMigration();

    expect(Refund::query()->where('transaction_id', $transaction->id)->sole()->amount_thousandths)->toBe(12500);
});

it('does not duplicate a JSON string history when the migration runs twice', function () use ($legacyHistory): void {
    $transaction = transactionWithRawPayload(json_encode($legacyHistory));

    runRefundsMigration(times: 2);

    expect(Refund::query()->where('transaction_id', $transaction->id)->count())->toBe(1);
});

it('does not report a readable payload without refunds', function (): void {
    transactionWithRawPayload(json_encode(['posID' => '90001', 'merchantRef' => 'MREF-NO-REFUNDS']));

    $logged = runRefundsMigration();

    expect(Refund::query()->count())->toBe(0)
        ->and(loggedAt($logged, 'warning'))->toBeNull()
        ->and(loggedAt($logged, 'error'))->toBeNull();
});

it('logs an error for an envelope that cannot be opened, since it may hold refunds', function () use ($legacyHistory): void {
    $foreignKey = Encrypter::generateKey(config('app.cipher'));
    $sealed = transactionWithRawPayload(new Encrypter($foreignKey, config('app.cipher'))->encryptString(json_encode($legacyHistory)));
    transactionWithRawPayload(json_encode(['posID' => '90001']));

    $logged = runRefundsMigration();

    $error = loggedAt($logged, 'error');

    expect(Refund::query()->count())->toBe(0)
        ->and($error)->not->toBeNull()
        ->and($error->context['refunds_left_behind_transaction_ids'])->toBe([$sealed->id])
        ->and($error->context['undecodable_transaction_ids'])->toBe([]);
});

it('logs an error for an encrypted payload that opens but whose history is not valid JSON', function (): void {
    $broken = transactionWithRawPayload(Crypt::encryptString('{"refunds":[{"amount":12.5,"reason":"legacy"}'));

    $logged = runRefundsMigration();

    $error = loggedAt($logged, 'error');

    expect(Refund::query()->count())->toBe(0)
        ->and($error)->not->toBeNull()
        ->and($error->context['refunds_left_behind_transaction_ids'])->toBe([$broken->id]);
});

it('only warns about a clear-text payload that cannot be decoded and does not mention refunds', function (): void {
    $garbled = transactionWithRawPayload('{"posID":"90001"');

    $logged = runRefundsMigration();

    $warning = loggedAt($logged, 'warning');

    expect(loggedAt($logged, 'error'))->toBeNull()
        ->and($warning)->not->toBeNull()
        ->and($warning->context['undecodable_transaction_ids'])->toBe([$garbled->id]);
});

it('logs an error for an unreadable payload that mentions refunds', function (): void {
    $broken = transactionWithRawPayload('{"refunds":[{"amount":12.5,"reason":"legacy"}');

    $logged = runRefundsMigration();

    $error = loggedAt($logged, 'error');

    expect($error)->not->toBeNull()
        ->and($error->context['refunds_left_behind_transaction_ids'])->toBe([$broken->id]);
});

it('counts refund entries that are not arrays instead of dropping them silently', function () use ($legacyHistory): void {
    $payload = [...$legacyHistory, 'refunds' => [...$legacyHistory['refunds'], 'not-an-array', null]];
    $transaction = transactionWithRawPayload(json_encode($payload));

    $logged = runRefundsMigration();

    $warning = loggedAt($logged, 'warning');

    expect(Refund::query()->where('transaction_id', $transaction->id)->count())->toBe(1)
        ->and($warning)->not->toBeNull()
        ->and($warning->context['malformed_entries'])->toBe([$transaction->id => 2]);
});

it('logs an error for a readable payload whose refunds list is corrupted', function (): void {
    $corrupted = transactionWithRawPayload(json_encode(['posID' => '90001', 'refunds' => 'corrupted']));

    $logged = runRefundsMigration();

    $error = loggedAt($logged, 'error');

    expect(Refund::query()->count())->toBe(0)
        ->and($error)->not->toBeNull()
        ->and($error->context['refunds_left_behind_transaction_ids'])->toBe([$corrupted->id]);
});
