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
        ->expectsOutput('The --older-than option must be a whole number of days, zero or more.')
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

it('makes progress across successive runs instead of re-selecting the same rows', function (): void {
    $first = Transaction::factory()->create([
        'status' => 'completed',
        'created_at' => now()->subDays(95),
        'payload' => ['purchaseRequest' => 'blob-1'],
    ]);

    $second = Transaction::factory()->create([
        'status' => 'completed',
        'created_at' => now()->subDays(93),
        'payload' => ['purchaseRequest' => 'blob-2'],
    ]);

    $third = Transaction::factory()->create([
        'status' => 'completed',
        'created_at' => now()->subDays(91),
        'payload' => ['purchaseRequest' => 'blob-3'],
    ]);

    $this->artisan('sisp:prune-request-payloads', ['--limit' => 2])
        ->expectsOutput('Pruned the purchase request payload from 2 SISP transactions.')
        ->assertSuccessful();

    expect($first->refresh()->payload)->not->toHaveKey('purchaseRequest')
        ->and($second->refresh()->payload)->not->toHaveKey('purchaseRequest')
        ->and($third->refresh()->payload)->toHaveKey('purchaseRequest');

    $this->artisan('sisp:prune-request-payloads', ['--limit' => 2])
        ->expectsOutput('Pruned the purchase request payload from 1 SISP transactions.')
        ->assertSuccessful();

    expect($third->refresh()->payload)->not->toHaveKey('purchaseRequest');
});

it('prunes a transaction that becomes terminal after a higher-id transaction was already pruned', function (): void {
    $pending = Transaction::factory()->create([
        'status' => 'pending',
        'created_at' => now()->subDays(95),
        'payload' => ['purchaseRequest' => 'blob-pending'],
    ]);

    $alreadyPruned = Transaction::factory()->create([
        'status' => 'completed',
        'created_at' => now()->subDays(91),
        'payload' => ['purchaseRequest' => 'blob-completed'],
    ]);

    expect($pending->id)->toBeLessThan($alreadyPruned->id);

    $this->artisan('sisp:prune-request-payloads')
        ->expectsOutput('Pruned the purchase request payload from 1 SISP transactions.')
        ->assertSuccessful();

    expect($alreadyPruned->refresh()->payload)->not->toHaveKey('purchaseRequest')
        ->and($pending->refresh()->payload)->toHaveKey('purchaseRequest');

    $pending->update(['status' => 'failed']);

    $this->artisan('sisp:prune-request-payloads')
        ->expectsOutput('Pruned the purchase request payload from 1 SISP transactions.')
        ->assertSuccessful();

    expect($pending->refresh()->payload)->not->toHaveKey('purchaseRequest');
});

it('marks an undecryptable payload as pruned without changing it and continues with the batch', function (): void {
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
        ->and($undecryptable->request_payload_pruned_at)->not->toBeNull()
        ->and($healthy->refresh()->payload)->not->toHaveKey('purchaseRequest')
        ->and($healthy->payload)->toHaveKey('posID');
});

it('does not stall behind more undecryptable rows than the limit', function (): void {
    $undecryptable = Transaction::factory()->count(3)->create([
        'status' => 'completed',
        'created_at' => now()->subDays(92),
        'payload' => ['purchaseRequest' => 'base64-blob'],
    ]);

    DB::table(config('sisp.tables.transactions'))
        ->whereIn('id', $undecryptable->pluck('id'))
        ->update(['payload' => 'not-a-valid-ciphertext']);

    $healthy = Transaction::factory()->create([
        'status' => 'completed',
        'created_at' => now()->subDays(91),
        'payload' => ['posID' => '90', 'purchaseRequest' => 'base64-blob'],
    ]);

    $this->artisan('sisp:prune-request-payloads', ['--limit' => 2])->assertSuccessful();

    expect($healthy->refresh()->payload)->toHaveKey('purchaseRequest');

    $this->artisan('sisp:prune-request-payloads', ['--limit' => 2])
        ->expectsOutput('Pruned the purchase request payload from 1 SISP transactions.')
        ->assertSuccessful();

    expect($healthy->refresh()->payload)->not->toHaveKey('purchaseRequest');
});

it('counts rows that only get marked against the limit', function (): void {
    $clean = Transaction::factory()->count(3)->create([
        'status' => 'completed',
        'created_at' => now()->subDays(91),
        'payload' => ['posID' => '90'],
    ]);

    $this->artisan('sisp:prune-request-payloads', ['--limit' => 1])->assertSuccessful();

    expect(Transaction::query()->whereIn('id', $clean->pluck('id'))->whereNotNull('request_payload_pruned_at')->count())->toBe(1);
});

it('refuses a negative configured window', function (): void {
    config()->set('sisp.prune_request_payloads_after_days', -1);
    $transaction = Transaction::factory()->create([
        'status' => 'completed',
        'created_at' => now(),
        'payload' => ['purchaseRequest' => 'base64-blob'],
    ]);

    $this->artisan('sisp:prune-request-payloads')
        ->expectsOutput('The sisp.prune_request_payloads_after_days setting cannot be negative.')
        ->assertFailed();

    expect($transaction->refresh()->payload)->toHaveKey('purchaseRequest');
});

it('marks a transaction with no payload as pruned so it stops being selected', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'completed',
        'created_at' => now()->subDays(91),
    ]);

    DB::table(config('sisp.tables.transactions'))
        ->where('id', $transaction->id)
        ->update(['payload' => null]);

    $this->artisan('sisp:prune-request-payloads')->assertSuccessful();

    expect($transaction->refresh()->request_payload_pruned_at)->not->toBeNull();
});

it('prunes the payload read under the row lock, keeping a refund written after the batch was loaded', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'completed',
        'created_at' => now()->subDays(91),
        'payload' => ['purchaseRequest' => 'base64-blob'],
    ]);

    Transaction::retrieved(function (Transaction $retrieved) use ($transaction): void {
        static $written = false;

        if ($written || $retrieved->id !== $transaction->id) {
            return;
        }

        $written = true;
        $fresh = Transaction::query()->find($transaction->id);
        $fresh->update(['payload' => [...$fresh->payload, 'refunds' => [['reason' => 'late']]]]);
    });

    $this->artisan('sisp:prune-request-payloads')->assertSuccessful();

    expect($transaction->refresh()->payload)->toBe(['refunds' => [['reason' => 'late']]]);
});

it('leaves a transaction another run pruned after this batch was loaded', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'completed',
        'created_at' => now()->subDays(91),
        'payload' => ['purchaseRequest' => 'base64-blob'],
    ]);

    Transaction::retrieved(function (Transaction $retrieved) use ($transaction): void {
        static $pruned = false;

        if ($pruned || $retrieved->id !== $transaction->id) {
            return;
        }

        $pruned = true;
        DB::table(config('sisp.tables.transactions'))
            ->where('id', $transaction->id)
            ->update(['request_payload_pruned_at' => now()]);
    });

    $this->artisan('sisp:prune-request-payloads')
        ->expectsOutput('No SISP request payloads needed pruning.')
        ->assertSuccessful();

    expect($transaction->refresh()->payload)->toHaveKey('purchaseRequest');
});
