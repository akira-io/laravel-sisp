<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RefundTransactionAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    config()->set('sisp.sandbox', true);

    foreach ([
        'update_laravel_sisp_transactions_add_request_payload_pruned_at',
        'create_sisp_refunds_table',
        'update_laravel_sisp_transactions_add_callback_error_fields',
    ] as $migration) {
        (include __DIR__."/../../database/migrations/{$migration}.php")->down();
    }
});

function callbackOnTheTwoOneSchema(Transaction $transaction, string $status): void
{
    $payload = Sisp::generateSandboxPayload(PaymentRequestData::from([
        'amount' => $transaction->amount,
        'merchantRef' => $transaction->merchant_ref,
        'merchantSession' => $transaction->merchant_session,
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => $transaction->currency,
        'transactionCode' => '1',
    ]), $status);

    test()->post(route('sisp.callback'), $payload->toArray())
        ->assertRedirect(route('sisp.callback', ['ref' => $transaction->merchant_ref]));
}

it('runs on a schema without the 2.2 migrations', function (): void {
    $transactions = Schema::getColumnListing('sisp_transactions');

    expect($transactions)->not->toContain('error_code', 'error_message', 'callback_raw_payload', 'request_payload_pruned_at')
        ->and(Schema::hasTable('sisp_refunds'))->toBeFalse();
});

it('completes a transaction from a signed callback', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending', 'amount' => 20, 'currency' => '132']);

    callbackOnTheTwoOneSchema($transaction, 'success');

    expect($transaction->refresh()->status)->toBe(TransactionStatus::completed);
});

it('fails a transaction from a signed refusal', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending', 'amount' => 20, 'currency' => '132']);

    callbackOnTheTwoOneSchema($transaction, 'failed');

    expect($transaction->refresh()->status)->toBe(TransactionStatus::failed);
});

it('refunds against the payload history when the refunds table is missing', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);
    $action = resolve(RefundTransactionAction::class);

    $action->handle($transaction, 60.0, 'partial_request');

    expect(fn () => $action->handle($transaction, 60.0, 'partial_request'))
        ->toThrow(LogicException::class, 'Refund amount (60) exceeds refundable balance.');

    $action->handle($transaction, 40.0, 'partial_request');

    expect($transaction->refresh()->status)->toBe(TransactionStatus::refunded)
        ->and($transaction->payload['refunds'])->toHaveCount(2);
});

it('rereads the payload history so a stale instance cannot refund twice without the refunds table', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);
    $stale = Transaction::query()->where('id', $transaction->id)->sole();

    resolve(RefundTransactionAction::class)->handle($transaction, 60.0, 'partial_request');

    expect(fn () => resolve(RefundTransactionAction::class)->handle($stale, 60.0, 'partial_request'))
        ->toThrow(LogicException::class, 'Refund amount (60) exceeds refundable balance.')
        ->and($transaction->refresh()->payload['refunds'])->toHaveCount(1);
});

it('refuses to prune request payloads until the migration has run', function (): void {
    $this->artisan('sisp:prune-request-payloads')
        ->expectsOutputToContain('Publish and run the laravel-sisp migrations first.')
        ->assertFailed();
});
