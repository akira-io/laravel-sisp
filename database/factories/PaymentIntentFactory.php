<?php

declare(strict_types=1);

namespace Akira\Sisp\Database\Factories;

use Akira\Sisp\Models\PaymentIntent;
use Akira\Sisp\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentIntent>
 */
final class PaymentIntentFactory extends Factory
{
    protected $model = PaymentIntent::class;

    public function definition(): array
    {
        return [
            'idempotency_key' => $this->faker->uuid(),
            'transaction_id' => Transaction::factory(),
            'status' => 'submitted',
        ];
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'transaction_id' => null,
            'status' => 'processing',
        ]);
    }
}
