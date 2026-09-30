<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RecordRefundAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Sisp;
use Akira\Sisp\Tests\Fixtures\RefundRouteUser;
use Illuminate\Support\Facades\Http;

function recordableTransaction(): Transaction
{
    return Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);
}

it('sends nothing to the gateway when a refund is recorded', function (): void {
    Http::preventStrayRequests();
    Http::fake();

    resolve(RecordRefundAction::class)->handle(recordableTransaction(), 100.0, 'customer_request');

    Http::assertNothingSent();
});

it('sends nothing to the gateway through the fluent builder', function (): void {
    Http::preventStrayRequests();
    Http::fake();

    resolve(Sisp::class)->refund(recordableTransaction())->full()->record();

    Http::assertNothingSent();
});

it('keeps the signed refund request on the recorded refund', function (): void {
    $transaction = recordableTransaction();

    resolve(RecordRefundAction::class)->handle($transaction, 100.0, 'customer_request');

    $request = $transaction->refunds()->sole()->request;

    expect($request)->toHaveKeys(['posID', 'merchantRef', 'amount', 'transactionCode', 'fingerprint']);
});

it('sends nothing to the gateway through the route', function (): void {
    Http::preventStrayRequests();
    Http::fake();

    allowRefunds();

    $this->actingAs(new RefundRouteUser)
        ->postJson(route('sisp.refund', recordableTransaction()), ['amount' => 100.0])
        ->assertOk();

    Http::assertNothingSent();
});
