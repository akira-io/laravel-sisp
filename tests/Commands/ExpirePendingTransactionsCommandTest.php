<?php

declare(strict_types=1);

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\DB;

it('cancels a pending transaction older than the window', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => null,
        'created_at' => now()->subDays(31),
    ]);

    $this->artisan('sisp:expire-pending')->assertSuccessful();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::cancelled);
});

it('leaves a pending transaction inside the window alone', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => null,
        'created_at' => now()->subDays(29),
    ]);

    $this->artisan('sisp:expire-pending')->assertSuccessful();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::pending);
});

it('leaves a pending transaction that received a callback alone', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => '6',
        'created_at' => now()->subDays(31),
    ]);

    $this->artisan('sisp:expire-pending')->assertSuccessful();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::pending);
});

it('records the transition in the transaction log', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => null,
        'created_at' => now()->subDays(31),
    ]);

    $this->artisan('sisp:expire-pending')->assertSuccessful();

    expect($transaction->refresh()->logs)->not->toBeEmpty();
});

it('honours an explicit window', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => null,
        'created_at' => now()->subDays(10),
    ]);

    $this->artisan('sisp:expire-pending', ['--older-than' => 5])->assertSuccessful();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::cancelled);
});

it('reports when no pending transactions are old enough to expire', function (): void {
    Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => null,
        'created_at' => now()->subDays(10),
    ]);

    $this->artisan('sisp:expire-pending')
        ->expectsOutput('No pending SISP transactions are old enough to expire.')
        ->assertSuccessful();
});

it('honours an explicit limit', function (): void {
    Transaction::factory()->count(2)->create([
        'status' => 'pending',
        'message_type' => null,
        'created_at' => now()->subDays(31),
    ]);

    $this->artisan('sisp:expire-pending', ['--limit' => 1])
        ->expectsOutput('Expired 1 pending SISP transactions older than 30 days.')
        ->assertSuccessful();

    expect(Transaction::query()->where('status', TransactionStatus::cancelled->value)->count())->toBe(1);
});

it('rejects a negative window instead of expiring transactions created moments ago', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => null,
        'created_at' => now(),
    ]);

    $this->artisan('sisp:expire-pending', ['--older-than' => -5])
        ->expectsOutput('The --older-than option must be at least 1 day.')
        ->assertFailed();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::pending);
});

it('rejects a zero window instead of expiring transactions created moments ago', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => null,
        'created_at' => now(),
    ]);

    $this->artisan('sisp:expire-pending', ['--older-than' => 0])
        ->expectsOutput('The --older-than option must be at least 1 day.')
        ->assertFailed();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::pending);
});

it('expires a pending transaction whose message_type is an empty string', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => '',
        'created_at' => now()->subDays(31),
    ]);

    $this->artisan('sisp:expire-pending')->assertSuccessful();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::cancelled);
});

it('leaves a pending transaction with a real message_type alone', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => '8',
        'created_at' => now()->subDays(31),
    ]);

    $this->artisan('sisp:expire-pending')->assertSuccessful();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::pending);
});

it('skips a transaction that cannot be cancelled and still processes the rest of the batch', function (): void {
    $racy = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => null,
        'created_at' => now()->subDays(31),
    ]);

    $stillPending = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => null,
        'created_at' => now()->subDays(31),
    ]);

    Transaction::retrieved(function (Transaction $transaction) use ($racy): void {
        if ($transaction->getKey() === $racy->getKey()) {
            DB::table(config('sisp.tables.transactions'))
                ->where('id', $racy->getKey())
                ->update(['status' => TransactionStatus::completed->value]);
        }
    });

    $this->artisan('sisp:expire-pending')
        ->expectsOutput('Expired 1 pending SISP transactions older than 30 days.')
        ->expectsOutput('Skipped 1 transactions that could not be cancelled.')
        ->assertSuccessful();

    expect($racy->refresh()->status)->toBe(TransactionStatus::completed)
        ->and($stillPending->refresh()->status)->toBe(TransactionStatus::cancelled);
});

it('is idempotent across two runs', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => null,
        'created_at' => now()->subDays(31),
    ]);

    $this->artisan('sisp:expire-pending')->assertSuccessful();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::cancelled);

    $this->artisan('sisp:expire-pending')
        ->expectsOutput('No pending SISP transactions are old enough to expire.')
        ->assertSuccessful();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::cancelled);
});
