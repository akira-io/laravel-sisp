<?php

declare(strict_types=1);

use Akira\Sisp\Events\RefundRecorded;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Event;

it('processes a full refund through the builder', function (): void {
    Event::fake();

    $transaction = Transaction::factory()->create([
        'status' => 'completed',
        'amount' => 90.0,
        'transaction_id' => 'TX-BUILDER-1',
        'response_code' => '001',
    ]);

    $refunded = Sisp::refund($transaction)
        ->full()
        ->reason('builder_refund')
        ->record();

    expect($refunded->status->value)->toBe('refunded')
        ->and($refunded->merchant_response)->toBe('builder_refund::90');

    Event::assertDispatched(RefundRecorded::class);
});

it('processes a partial refund through the builder', function (): void {
    Event::fake();

    $transaction = Transaction::factory()->create([
        'status' => 'completed',
        'amount' => 100.0,
        'transaction_id' => 'TX-BUILDER-2',
        'response_code' => '001',
    ]);

    $refunded = Sisp::refund($transaction)
        ->amount(40.0)
        ->reason('partial_refund')
        ->record();

    expect($refunded->status->value)->toBe('completed')
        ->and($refunded->refunded_at)->not->toBeNull();
});

it('requires an amount before processing', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'completed', 'amount' => 50.0]);

    Sisp::refund($transaction)->record();
})->throws(LogicException::class, 'A refund amount is required. Call amount() or full() first.');

it('forwards the idempotency key through the builder', function (): void {
    Event::fake();

    $transaction = Transaction::factory()->create([
        'status' => 'completed',
        'amount' => 90.0,
        'transaction_id' => 'TX-BUILDER-KEY',
        'response_code' => '001',
    ]);

    Sisp::refund($transaction)->amount(30.0)->idempotencyKey('builder-key')->record();
    Sisp::refund($transaction)->amount(30.0)->idempotencyKey('builder-key')->record();

    expect($transaction->refunds()->sole()->idempotency_key)->toBe('builder-key');

    Event::assertDispatchedTimes(RefundRecorded::class, 1);
});
