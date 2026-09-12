<?php

declare(strict_types=1);

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;

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
        ->expectsOutput('The --older-than option cannot be negative.')
        ->assertFailed();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::pending);
});

it('treats an explicit zero window as everything without a callback, whatever its age', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => null,
        'created_at' => now(),
    ]);

    $this->artisan('sisp:expire-pending', ['--older-than' => 0])->assertSuccessful();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::cancelled);
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
