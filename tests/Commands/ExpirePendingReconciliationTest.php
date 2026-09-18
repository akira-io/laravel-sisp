<?php

declare(strict_types=1);

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');
    config()->set('sisp.transaction_status.reconciliation_enabled', true);
});

function expirable_pending_transaction(): Transaction
{
    return Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => null,
        'created_at' => now()->subDays(31),
    ]);
}

it('completes a transaction SISP reports as paid instead of cancelling it', function (): void {
    Http::fake(['*' => Http::response(['result' => true, 'transactionSuccess' => true, 'transactionStatusDescription' => 'E-OK'])]);
    $transaction = expirable_pending_transaction();

    $this->artisan('sisp:expire-pending')
        ->expectsOutput('Left 1 transactions that SISP settled or could not be asked about.')
        ->assertSuccessful();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::completed)
        ->and($transaction->cancelled_at)->toBeNull();
});

it('records a failure SISP reports instead of cancelling it', function (): void {
    Http::fake(['*' => Http::response(['result' => true, 'transactionSuccess' => false, 'transactionStatusDescription' => 'E-ERRO'])]);
    $transaction = expirable_pending_transaction();

    $this->artisan('sisp:expire-pending')->assertSuccessful();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::failed);
});

it('cancels a transaction SISP has no record of', function (): void {
    Http::fake(['*' => Http::response(['result' => false, 'msg' => 'Not found'])]);
    $transaction = expirable_pending_transaction();

    $this->artisan('sisp:expire-pending')
        ->expectsOutput('Expired 1 pending SISP transactions older than 30 days.')
        ->assertSuccessful();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::cancelled);
});

it('leaves a transaction pending when SISP cannot be asked', function (): void {
    Http::fake(fn () => throw new ConnectionException('timeout'));
    $transaction = expirable_pending_transaction();

    $this->artisan('sisp:expire-pending')
        ->expectsOutput('Expired 0 pending SISP transactions older than 30 days.')
        ->assertSuccessful();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::pending);
});

it('does not ask SISP when reconciliation is disabled', function (): void {
    config()->set('sisp.transaction_status.reconciliation_enabled', false);
    Http::fake();
    $transaction = expirable_pending_transaction();

    $this->artisan('sisp:expire-pending')->assertSuccessful();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::cancelled);
    Http::assertNothingSent();
});

it('leaves a transaction pending when SISP answers with an HTTP error', function (int $status): void {
    Http::fake(['*' => Http::response(['result' => false], $status)]);
    $transaction = expirable_pending_transaction();

    $this->artisan('sisp:expire-pending')
        ->expectsOutput('Expired 0 pending SISP transactions older than 30 days.')
        ->assertSuccessful();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::pending);
})->with([401, 500, 503]);

it('leaves a transaction pending when SISP answers with something other than JSON', function (): void {
    Http::fake(['*' => Http::response('<html>maintenance</html>')]);
    $transaction = expirable_pending_transaction();

    $this->artisan('sisp:expire-pending')->assertSuccessful();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::pending);
});
