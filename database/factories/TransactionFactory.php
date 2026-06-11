<?php

declare(strict_types=1);

namespace Akira\Sisp\Database\Factories;

use Akira\Sisp\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
final class TransactionFactory extends Factory
{
    #[\Override]
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'merchant_ref' => $this->faker->uuid(),
            'merchant_session' => $this->faker->uuid(),
            'amount' => $this->faker->numberBetween(1000, 100000),
            'currency' => 'CVE',
            'status' => 'pending',
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->email(),
            'customer_phone' => $this->faker->phoneNumber(),
            'customer_city' => $this->faker->city(),
            'customer_address' => $this->faker->address(),
            'customer_country' => 'cv',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'completed',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'failed',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'cancelled',
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'refunded',
        ]);
    }

    public function withoutEmail(): static
    {
        return $this->state(fn (array $attributes): array => [
            'customer_email' => null,
        ]);
    }
}
