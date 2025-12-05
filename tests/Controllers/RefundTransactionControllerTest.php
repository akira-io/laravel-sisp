<?php

declare(strict_types=1);

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Http\Controllers\RefundTransactionController;
use Akira\Sisp\Models\Transaction;
use Illuminate\Http\Request;

it('refunds a completed transaction and returns json', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
    ]);

    $controller = resolve(RefundTransactionController::class);
    $request = Request::create(route('sisp.refund'), 'POST', ['amount' => 50.0, 'reason' => 'test']);

    $response = $controller($t, $request);

    expect($response->getStatusCode())->toBe(200);
    $data = $response->getData(true);
    expect($data['success'])->toBeTrue();
});

it('returns 400 when refund amount exceeds transaction', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'completed',
        'amount' => 100.0,
    ]);

    $controller = resolve(RefundTransactionController::class);
    $request = Request::create('/sisp/refund', 'POST', ['amount' => 150.0]);

    $response = $controller($t, $request);
    expect($response->getStatusCode())->toBe(400);
});
