<?php

declare(strict_types=1);

namespace Akira\Sisp\Database\Factories;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionItem>
 */
final class TransactionItemFactory extends Factory
{
    protected $model = TransactionItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 5);
        $unitPrice = $this->faker->numberBetween(1000, 50000);
        $totalPrice = $quantity * $unitPrice;

        return [
            'transaction_id' => Transaction::factory(),
            'product_id' => (string) $this->faker->numberBetween(1, 1000),
            'product_name' => $this->faker->word(),
            'quantity' => $quantity,
            'unit_price_cents' => $unitPrice,
            'total_price_cents' => $totalPrice,
            'description' => $this->faker->sentence(),
            'metadata' => [
                'kind' => 'passenger',
                'first_name' => $this->faker->firstName(),
                'last_name' => $this->faker->lastName(),
                'phone' => $this->faker->phoneNumber(),
                'email' => $this->faker->email(),
                'doc_number' => $this->faker->numerify('########'),
                'doc_type' => $this->faker->numberBetween(1, 5),
                'route_id' => 1,
                'birthdate' => $this->faker->date('Y-m-d', '2000-01-01'),
                'country' => 'cv',
                'schedule_id' => 1,
                'departure_date' => $this->faker->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            ],
        ];
    }

    public function forTransaction(Transaction $transaction): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_id' => $transaction->id,
        ]);
    }

    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], $metadata),
        ]);
    }
}
