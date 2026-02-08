<?php

declare(strict_types=1);

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Http\Controllers\RefundTransactionController;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Sisp;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

afterEach(function (): void {
    $reflection = new ReflectionClass(Sisp::class);
    $property = $reflection->getProperty('authCallback');
    $property->setValue(null, null);
});

it('allows refund when auth callback is not set (default)', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
    ]);

    $controller = resolve(RefundTransactionController::class);
    $request = Request::create(route('sisp.refund', $t), 'POST', ['amount' => 50.0]);

    $response = $controller($t, $request);

    expect($response->getStatusCode())->toBe(200);
});

it('allows refund when auth callback returns true', function (): void {
    Sisp::auth(fn (): true => true);

    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
    ]);

    $controller = resolve(RefundTransactionController::class);
    $request = Request::create(route('sisp.refund', $t), 'POST', ['amount' => 50.0]);

    $response = $controller($t, $request);

    expect($response->getStatusCode())->toBe(200);
});

it('denies refund when auth callback returns false', function (): void {
    Sisp::auth(fn (): false => false);

    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
    ]);

    $controller = resolve(RefundTransactionController::class);
    $request = Request::create(route('sisp.refund', $t), 'POST', ['amount' => 50.0]);

    expect(fn () => $controller($t, $request))
        ->toThrow(HttpException::class, 'Unauthorized action.');
});

it('passes request and transaction to auth callback', function (): void {
    $called = false;
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
    ]);

    Sisp::auth(function (Request $request, ?Transaction $transaction) use (&$called, $t): true {
        $called = true;
        expect($transaction->id)->toBe($t->id);
        expect($request->input('amount'))->toBe(50.0);

        return true;
    });

    $controller = resolve(RefundTransactionController::class);
    $request = Request::create(route('sisp.refund', $t), 'POST', ['amount' => 50.0]);

    $controller($t, $request);

    expect($called)->toBeTrue();
});
