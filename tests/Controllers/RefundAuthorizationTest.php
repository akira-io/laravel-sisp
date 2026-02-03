<?php

declare(strict_types=1);

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Http\Controllers\RefundTransactionController;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Sisp;
use Illuminate\Http\Request;

it('allows refund when auth callback returns true', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
    ]);

    Sisp::auth(fn () => true);

    $controller = resolve(RefundTransactionController::class);
    $request = Request::create(route('sisp.refund', $t), 'POST', ['amount' => 50.0]);

    $response = $controller($t, $request);

    expect($response->getStatusCode())->toBe(200);
});

it('denies refund when auth callback returns false', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
    ]);

    Sisp::auth(fn () => false);

    $controller = resolve(RefundTransactionController::class);
    $request = Request::create(route('sisp.refund', $t), 'POST', ['amount' => 50.0]);

    $response = $controller($t, $request);

    expect($response->getStatusCode())->toBe(403);
});

it('allows refund in testing environment by default', function (): void {
    // Reset callback using reflection since there is no public unset method
    $reflection = new ReflectionClass(Sisp::class);
    $property = $reflection->getProperty('authCallback');
    $property->setAccessible(true);
    $property->setValue(null);

    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
    ]);

    $controller = resolve(RefundTransactionController::class);
    $request = Request::create(route('sisp.refund', $t), 'POST', ['amount' => 50.0]);

    $response = $controller($t, $request);

    expect($response->getStatusCode())->toBe(200);
});
