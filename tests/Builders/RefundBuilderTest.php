<?php

declare(strict_types=1);

use Akira\Sisp\Events\TransactionRefunded;
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
        ->process();

    expect($refunded->status->value)->toBe('refunded')
        ->and($refunded->merchant_response)->toBe('builder_refund::90');

    Event::assertDispatched(TransactionRefunded::class);
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
        ->process();

    expect($refunded->status->value)->toBe('completed')
        ->and($refunded->refunded_at)->not->toBeNull();
});

it('requires an amount before processing', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'completed', 'amount' => 50.0]);

    Sisp::refund($transaction)->process();
})->throws(LogicException::class, 'A refund amount is required. Call amount() or full() first.');
