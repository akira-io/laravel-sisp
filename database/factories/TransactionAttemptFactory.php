<?php

declare(strict_types=1);

namespace Akira\Sisp\Database\Factories;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<TransactionAttempt>
 */
final class TransactionAttemptFactory extends Factory
{
    #[Override]
    protected $model = TransactionAttempt::class;

    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'attempt_number' => 1,
            'merchant_ref' => $this->faker->uuid(),
            'merchant_session' => $this->faker->uuid(),
            'attempt_session' => $this->faker->uuid(),
            'status' => 'pending',
            'payload' => [],
            'submitted_at' => now(),
        ];
    }

    public function forTransaction(Transaction $transaction): static
    {
        return $this->state(fn (array $attributes): array => [
            'transaction_id' => $transaction->id,
            'merchant_ref' => $transaction->merchant_ref,
        ]);
    }
}
