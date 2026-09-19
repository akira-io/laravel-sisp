<?php

declare(strict_types=1);

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Events\TransactionRefunded;
use Akira\Sisp\Mcp\Servers\SispOpsServer;
use Akira\Sisp\Mcp\Tools\Ops\CancelTransactionTool;
use Akira\Sisp\Mcp\Tools\Ops\RefundTransactionTool;
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Event;

it('refunds a completed transaction in full', function (): void {
    $transaction = Transaction::factory()->completed()->create([
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    SispOpsServer::tool(RefundTransactionTool::class, ['transaction' => (string) $transaction->id, 'amount' => 100.0])
        ->assertOk()
        ->assertSee('refunded');

    expect($transaction->fresh()->status)->toBe(TransactionStatus::refunded);
});

it('refunds a completed transaction partially', function (): void {
    $transaction = Transaction::factory()->completed()->create([
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    SispOpsServer::tool(RefundTransactionTool::class, [
        'transaction' => (string) $transaction->id,
        'amount' => 40.0,
        'reason' => 'partial_return',
    ])->assertOk();

    expect($transaction->fresh()->status)->toBe(TransactionStatus::completed)
        ->and($transaction->refunds()->sum('amount_thousandths'))->toBe(40000);
});

it('fails to refund a pending transaction', function (): void {
    $transaction = Transaction::factory()->pending()->create();

    SispOpsServer::tool(RefundTransactionTool::class, ['transaction' => (string) $transaction->id, 'amount' => 10.0])
        ->assertHasErrors(['cannot be refunded']);
});

it('cancels a pending transaction', function (): void {
    $transaction = Transaction::factory()->pending()->create();

    SispOpsServer::tool(CancelTransactionTool::class, ['transaction' => (string) $transaction->id])
        ->assertOk()
        ->assertSee('cancelled');

    expect($transaction->fresh()->status)->toBe(TransactionStatus::cancelled);
});

it('fails to cancel a completed transaction', function (): void {
    $transaction = Transaction::factory()->completed()->create();

    SispOpsServer::tool(CancelTransactionTool::class, ['transaction' => (string) $transaction->id])
        ->assertHasErrors(["Transaction with status 'completed' cannot be cancelled."]);
});

it('validates the refund payload with the http refund request rules', function (array $arguments, string $field): void {
    $transaction = Transaction::factory()->completed()->create(['amount' => 100.0, 'merchant_ref' => 'REF-RULES']);

    SispOpsServer::tool(RefundTransactionTool::class, ['transaction' => 'REF-RULES', ...$arguments])
        ->assertHasErrors([$field]);

    expect($transaction->fresh()->status)->toBe(TransactionStatus::completed)
        ->and($transaction->refunds()->count())->toBe(0);
})->with([
    'missing amount' => [[], 'amount'],
    'zero amount' => [['amount' => 0], 'amount'],
    'non-numeric amount' => [['amount' => 'all'], 'amount'],
    'reason too long' => [['amount' => 10, 'reason' => str_repeat('x', 256)], 'reason'],
]);

it('refuses to refund more than the refundable balance', function (): void {
    $transaction = Transaction::factory()->completed()->create([
        'amount' => 100.0,
        'merchant_ref' => 'REF-OVER',
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    SispOpsServer::tool(RefundTransactionTool::class, ['transaction' => 'REF-OVER', 'amount' => 60.0])->assertOk();

    SispOpsServer::tool(RefundTransactionTool::class, ['transaction' => 'REF-OVER', 'amount' => 60.0])
        ->assertHasErrors(['exceeds refundable balance']);

    expect($transaction->refunds()->sum('amount_thousandths'))->toBe(60000);
});

it('reports why a failed or refunded transaction cannot be cancelled', function (string $status): void {
    $transaction = Transaction::factory()->create(['status' => $status, 'merchant_ref' => 'REF-FINAL']);

    SispOpsServer::tool(CancelTransactionTool::class, ['transaction' => 'REF-FINAL'])
        ->assertHasErrors(["Transaction with status '{$status}' cannot be cancelled."]);

    expect($transaction->fresh()->status->value)->toBe($status);
})->with(['failed', 'refunded']);

it('refunds once when a retried call repeats the idempotency key', function (): void {
    Event::fake([TransactionRefunded::class]);
    $transaction = Transaction::factory()->completed()->create([
        'amount' => 100.0,
        'merchant_ref' => 'REF-RETRY',
        'transaction_id' => '123',
        'response_code' => '5',
    ]);
    $arguments = ['transaction' => 'REF-RETRY', 'amount' => 40.0, 'idempotency_key' => 'agent-retry-1'];

    SispOpsServer::tool(RefundTransactionTool::class, $arguments)->assertOk();
    SispOpsServer::tool(RefundTransactionTool::class, $arguments)->assertOk();

    expect($transaction->refunds()->count())->toBe(1)
        ->and($transaction->refunds()->sum('amount_thousandths'))->toBe(40000);

    Event::assertDispatchedTimes(TransactionRefunded::class, 1);
});

it('refuses to reuse an idempotency key for another amount', function (): void {
    $transaction = Transaction::factory()->completed()->create([
        'amount' => 100.0,
        'merchant_ref' => 'REF-REUSE',
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    SispOpsServer::tool(RefundTransactionTool::class, ['transaction' => 'REF-REUSE', 'amount' => 40.0, 'idempotency_key' => 'agent-key'])->assertOk();

    SispOpsServer::tool(RefundTransactionTool::class, ['transaction' => 'REF-REUSE', 'amount' => 30.0, 'idempotency_key' => 'agent-key'])
        ->assertHasErrors(['Refund failed']);

    expect($transaction->refunds()->count())->toBe(1);
});
