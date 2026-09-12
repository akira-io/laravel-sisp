<?php

declare(strict_types=1);

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

final class RefundRouteUser implements Authenticatable
{
    public function __construct(
        public int $id = 1,
        public string $email = 'buyer@example.com',
    ) {}

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): int
    {
        return $this->id;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void {}

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    public function can(string $ability, mixed $arguments = []): bool
    {
        return Gate::forUser($this)->check($ability, $arguments);
    }
}

function refundableTransaction(float $amount = 100.0): Transaction
{
    return Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => $amount,
        'customer_email' => 'buyer@example.com',
        'transaction_id' => '123',
        'response_code' => '5',
    ]);
}

function allowRefunds(bool $allowed = true): void
{
    Gate::define('refund', fn (RefundRouteUser $user, Transaction $transaction): bool => $allowed);
}

it('refunds a completed transaction and returns json', function (): void {
    allowRefunds();
    $transaction = refundableTransaction();

    $this->actingAs(new RefundRouteUser())
        ->postJson(route('sisp.refund', $transaction), ['amount' => 100.0, 'reason' => 'test'])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($transaction->refresh()->status)->toBe(TransactionStatus::refunded)
        ->and($transaction->merchant_response)->toBe('test::100');
});

it('defaults the reason when the payload omits it', function (): void {
    allowRefunds();
    $transaction = refundableTransaction();

    $this->actingAs(new RefundRouteUser())
        ->postJson(route('sisp.refund', $transaction), ['amount' => 100.0])
        ->assertOk();

    expect($transaction->refresh()->merchant_response)->toBe('user_refund::100');
});

it('allows partial refund amounts', function (): void {
    allowRefunds();
    $transaction = refundableTransaction();

    $this->actingAs(new RefundRouteUser())
        ->postJson(route('sisp.refund', $transaction), ['amount' => 50.0])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($transaction->refresh()->payload['refunds'][0]['request']['transactionCode'])->toBe('8');
});

it('returns 400 when refund amount exceeds transaction', function (): void {
    allowRefunds();
    $transaction = refundableTransaction();

    $this->actingAs(new RefundRouteUser())
        ->postJson(route('sisp.refund', $transaction), ['amount' => 150.0])
        ->assertStatus(400)
        ->assertJsonPath('success', false);

    expect($transaction->refresh()->status)->toBe(TransactionStatus::completed);
});

it('returns 403 when the gate denies the refund', function (): void {
    allowRefunds(false);
    $transaction = refundableTransaction();

    $this->actingAs(new RefundRouteUser(2, 'intruder@example.com'))
        ->postJson(route('sisp.refund', $transaction), ['amount' => 10.0])
        ->assertForbidden()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Unauthorized to refund this transaction.');

    expect($transaction->refresh()->status)->toBe(TransactionStatus::completed);
});

it('returns 403 when the customer email matches but the gate still denies', function (): void {
    allowRefunds(false);
    $transaction = refundableTransaction();

    $this->actingAs(new RefundRouteUser())
        ->postJson(route('sisp.refund', $transaction), ['amount' => 10.0])
        ->assertForbidden();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::completed);
});

it('rejects unauthenticated refund attempts', function (): void {
    allowRefunds();
    $transaction = refundableTransaction();

    $this->postJson(route('sisp.refund', $transaction), ['amount' => 10.0])
        ->assertUnauthorized();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::completed);
});

it('rejects an array amount instead of refunding one unit', function (): void {
    allowRefunds();
    $transaction = refundableTransaction();

    $this->actingAs(new RefundRouteUser())
        ->postJson(route('sisp.refund', $transaction), ['amount' => ['x']])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors('amount');

    expect($transaction->refresh()->status)->toBe(TransactionStatus::completed)
        ->and($transaction->payload['refunds'] ?? [])->toBe([]);
});

it('rejects an array reason instead of failing with a type error', function (): void {
    allowRefunds();
    $transaction = refundableTransaction();

    $this->actingAs(new RefundRouteUser())
        ->postJson(route('sisp.refund', $transaction), ['amount' => 10.0, 'reason' => ['x']])
        ->assertStatus(422)
        ->assertJsonValidationErrors('reason');

    expect($transaction->refresh()->status)->toBe(TransactionStatus::completed);
});

it('rejects invalid amounts', function (mixed $amount): void {
    allowRefunds();
    $transaction = refundableTransaction();

    $this->actingAs(new RefundRouteUser())
        ->postJson(route('sisp.refund', $transaction), ['amount' => $amount])
        ->assertStatus(422)
        ->assertJsonValidationErrors('amount');

    expect($transaction->refresh()->status)->toBe(TransactionStatus::completed);
})->with([
    'zero' => [0],
    'negative' => [-5],
    'non numeric' => ['abc'],
    'empty' => [''],
]);

it('requires an amount', function (): void {
    allowRefunds();
    $transaction = refundableTransaction();

    $this->actingAs(new RefundRouteUser())
        ->postJson(route('sisp.refund', $transaction), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('amount');
});

it('answers 403 before validating when the gate denies the refund', function (): void {
    allowRefunds(false);
    $transaction = refundableTransaction();

    $this->actingAs(new RefundRouteUser())
        ->postJson(route('sisp.refund', $transaction), ['amount' => ['x']])
        ->assertForbidden()
        ->assertJsonMissingPath('errors');

    expect($transaction->refresh()->status)->toBe(TransactionStatus::completed);
});
