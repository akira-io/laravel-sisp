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
        'customer_email' => 'buyer@example.com',
    ]);

    $controller = resolve(RefundTransactionController::class);
    $request = Request::create(route('sisp.refund', $t), 'POST', ['amount' => 100.0, 'reason' => 'test']);
    $request->setUserResolver(fn (): object => new class
    {
        public string $email = 'buyer@example.com';

        public function can(string $ability, mixed $subject): bool
        {
            return $ability === 'refund' && $subject instanceof Transaction;
        }
    });

    $response = $controller($t, $request);

    expect($response->getStatusCode())->toBe(200);
    $data = $response->getData(true);
    expect($data['success'])->toBeTrue();
});

it('returns 400 when refund amount exceeds transaction', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'completed',
        'amount' => 100.0,
        'customer_email' => 'buyer@example.com',
    ]);

    $controller = resolve(RefundTransactionController::class);
    $request = Request::create(route('sisp.refund', $t), 'POST', ['amount' => 150.0]);
    $request->setUserResolver(fn (): object => new class
    {
        public string $email = 'buyer@example.com';

        public function can(string $ability, mixed $subject): bool
        {
            return $ability === 'refund' && $subject instanceof Transaction;
        }
    });

    $response = $controller($t, $request);
    expect($response->getStatusCode())->toBe(400);
});

it('returns 400 when refund amount is below transaction total', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'completed',
        'amount' => 100.0,
        'customer_email' => 'buyer@example.com',
    ]);

    $controller = resolve(RefundTransactionController::class);
    $request = Request::create(route('sisp.refund', $t), 'POST', ['amount' => 50.0]);
    $request->setUserResolver(fn (): object => new class
    {
        public function can(string $ability, mixed $subject): bool
        {
            return $ability === 'refund' && $subject instanceof Transaction;
        }
    });

    $response = $controller($t, $request);
    expect($response->getStatusCode())->toBe(400)
        ->and($response->getData(true)['message'])->toContain('SISP only supports full-amount refunds')
        ->and($t->refresh()->amount)->toBe(100.0);
});

it('returns 403 when user is not authorized for the transaction', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'customer_email' => 'buyer@example.com',
    ]);

    $controller = resolve(RefundTransactionController::class);
    $request = Request::create(route('sisp.refund', $t), 'POST', ['amount' => 10.0]);
    $request->setUserResolver(fn (): object => new class
    {
        public string $email = 'intruder@example.com';

        public function can(string $ability, mixed $subject): bool
        {
            return false;
        }
    });

    $response = $controller($t, $request);

    expect($response->getStatusCode())->toBe(403);
    $data = $response->getData(true);
    expect($data['success'])->toBeFalse();
});

it('returns 403 when customer email matches but user lacks refund ability', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'customer_email' => 'buyer@example.com',
    ]);

    $controller = resolve(RefundTransactionController::class);
    $request = Request::create(route('sisp.refund', $t), 'POST', ['amount' => 10.0]);
    $request->setUserResolver(fn (): object => new class
    {
        public string $email = 'buyer@example.com';

        public function can(string $ability, mixed $subject): bool
        {
            return false;
        }
    });

    $response = $controller($t, $request);

    expect($response->getStatusCode())->toBe(403)
        ->and($response->getData(true)['success'])->toBeFalse()
        ->and($t->refresh()->status)->toBe(TransactionStatus::completed);
});

it('returns 403 when no authenticated user is present', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'customer_email' => 'buyer@example.com',
    ]);

    $controller = resolve(RefundTransactionController::class);
    $request = Request::create(route('sisp.refund', $t), 'POST', ['amount' => 10.0]);

    $response = $controller($t, $request);

    expect($response->getStatusCode())->toBe(403);
    $data = $response->getData(true);
    expect($data['success'])->toBeFalse();
});

it('returns 403 when user has no email and no refund ability', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'customer_email' => 'buyer@example.com',
    ]);

    $controller = resolve(RefundTransactionController::class);
    $request = Request::create(route('sisp.refund', $t), 'POST', ['amount' => 10.0]);
    $request->setUserResolver(fn (): object => new class
    {
        public function can(string $ability, mixed $subject): bool
        {
            return false;
        }
    });

    $response = $controller($t, $request);

    expect($response->getStatusCode())->toBe(403);
    $data = $response->getData(true);
    expect($data['success'])->toBeFalse();
});

it('allows authorized users through can refund ability', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'customer_email' => 'buyer@example.com',
    ]);

    $controller = resolve(RefundTransactionController::class);
    $request = Request::create(route('sisp.refund', $t), 'POST', ['amount' => 100.0, 'reason' => 'policy_allowed']);
    $request->setUserResolver(fn (): object => new class
    {
        public string $email = 'intruder@example.com';

        public function can(string $ability, mixed $subject): bool
        {
            return $ability === 'refund' && $subject instanceof Transaction;
        }
    });

    $response = $controller($t, $request);
    $data = $response->getData(true);

    expect($response->getStatusCode())->toBe(200)
        ->and($data['success'])->toBeTrue();
});
