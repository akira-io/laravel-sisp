<?php

declare(strict_types=1);

namespace Akira\Sisp\Database\Factories;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionAttempt>
 */
final class TransactionAttemptFactory extends Factory
{
    protected $model = TransactionAttempt::class;

    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'attempt_number' => 1,
            'merchant_ref' => $this->faker->uuid(),
            'merchant_session' => $this->faker->uuid(),
            'status' => 'pending',
            'payload' => [],
            'submitted_at' => now(),
        ];
    }
}
