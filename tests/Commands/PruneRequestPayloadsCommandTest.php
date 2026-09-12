<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\DB;

it('removes the purchase request from an old terminal transaction', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'completed',
        'created_at' => now()->subDays(91),
        'payload' => ['posID' => '90', 'purchaseRequest' => 'base64-blob'],
    ]);

    $this->artisan('sisp:prune-request-payloads')->assertSuccessful();

    expect($transaction->refresh()->payload)->not->toHaveKey('purchaseRequest')
        ->and($transaction->payload)->toHaveKey('posID');
});

it('leaves a transaction inside the window alone', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'completed',
        'created_at' => now()->subDays(89),
        'payload' => ['purchaseRequest' => 'base64-blob'],
    ]);

    $this->artisan('sisp:prune-request-payloads')->assertSuccessful();

    expect($transaction->refresh()->payload)->toHaveKey('purchaseRequest');
});

it('leaves a pending transaction alone whatever its age', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'pending',
        'created_at' => now()->subDays(400),
        'payload' => ['purchaseRequest' => 'base64-blob'],
    ]);

    $this->artisan('sisp:prune-request-payloads')->assertSuccessful();

    expect($transaction->refresh()->payload)->toHaveKey('purchaseRequest');
});

it('is idempotent', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'completed',
        'created_at' => now()->subDays(91),
        'payload' => ['posID' => '90', 'purchaseRequest' => 'base64-blob'],
    ]);

    $this->artisan('sisp:prune-request-payloads')->assertSuccessful();
    $first = $transaction->refresh()->payload;

    $this->artisan('sisp:prune-request-payloads')->assertSuccessful();

    expect($transaction->refresh()->payload)->toBe($first);
});

it('records the transition in the transaction log', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'completed',
        'created_at' => now()->subDays(91),
        'payload' => ['purchaseRequest' => 'base64-blob'],
    ]);

    $this->artisan('sisp:prune-request-payloads')->assertSuccessful();

    expect($transaction->refresh()->logs)->not->toBeEmpty();
});

it('reports when nothing needs pruning', function (): void {
    Transaction::factory()->create([
        'status' => 'completed',
        'created_at' => now()->subDays(89),
        'payload' => ['purchaseRequest' => 'base64-blob'],
    ]);

    $this->artisan('sisp:prune-request-payloads')
        ->expectsOutput('No SISP request payloads needed pruning.')
        ->assertSuccessful();
});

it('skips a terminal transaction that already has no purchase request', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'refunded',
        'created_at' => now()->subDays(91),
        'payload' => ['posID' => '90'],
    ]);

    $this->artisan('sisp:prune-request-payloads')
        ->expectsOutput('No SISP request payloads needed pruning.')
        ->assertSuccessful();

    expect($transaction->refresh()->payload)->toBe(['posID' => '90']);
});

it('honours an explicit window', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'failed',
        'created_at' => now()->subDays(10),
        'payload' => ['purchaseRequest' => 'base64-blob'],
    ]);

    $this->artisan('sisp:prune-request-payloads', ['--older-than' => 5])->assertSuccessful();

    expect($transaction->refresh()->payload)->not->toHaveKey('purchaseRequest');
});

it('honours an explicit limit', function (): void {
    Transaction::factory()->count(2)->create([
        'status' => 'cancelled',
        'created_at' => now()->subDays(91),
        'payload' => ['purchaseRequest' => 'base64-blob'],
    ]);

    $this->artisan('sisp:prune-request-payloads', ['--limit' => 1])
        ->expectsOutput('Pruned the purchase request payload from 1 SISP transactions.')
        ->assertSuccessful();
});

it('rejects a negative window instead of deleting personal data outside the retention policy', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'completed',
        'created_at' => now(),
        'payload' => ['purchaseRequest' => 'base64-blob'],
    ]);

    $this->artisan('sisp:prune-request-payloads', ['--older-than' => -5])
        ->expectsOutput('The --older-than option cannot be negative.')
        ->assertFailed();

    expect($transaction->refresh()->payload)->toHaveKey('purchaseRequest');
});

it('treats an explicit zero window as every terminal transaction, whatever its age', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'completed',
        'created_at' => now(),
        'payload' => ['purchaseRequest' => 'base64-blob'],
    ]);

    $this->artisan('sisp:prune-request-payloads', ['--older-than' => 0])->assertSuccessful();

    expect($transaction->refresh()->payload)->not->toHaveKey('purchaseRequest');
});

it('skips a transaction whose payload cannot be decrypted and continues pruning the rest of the batch', function (): void {
    $undecryptable = Transaction::factory()->create([
        'status' => 'completed',
        'created_at' => now()->subDays(92),
        'payload' => ['purchaseRequest' => 'base64-blob'],
    ]);

    DB::table(config('sisp.tables.transactions'))
        ->where('id', $undecryptable->id)
        ->update(['payload' => 'not-a-valid-ciphertext']);

    $healthy = Transaction::factory()->create([
        'status' => 'completed',
        'created_at' => now()->subDays(91),
        'payload' => ['posID' => '90', 'purchaseRequest' => 'base64-blob'],
    ]);

    $this->artisan('sisp:prune-request-payloads')
        ->expectsOutput('Pruned the purchase request payload from 1 SISP transactions.')
        ->assertSuccessful();

    expect($undecryptable->refresh()->payload)->toBe('not-a-valid-ciphertext')
        ->and($healthy->refresh()->payload)->not->toHaveKey('purchaseRequest')
        ->and($healthy->payload)->toHaveKey('posID');
});
