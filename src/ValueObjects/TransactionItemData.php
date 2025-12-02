<?php

declare(strict_types=1);

namespace Akira\Sisp\ValueObjects;

final readonly class TransactionItemData
{
    public function __construct(
        public string $product_name,
        public int $quantity,
        public float $unit_price,
        public float $total_price,
        public ?string $product_id = null,
        public ?string $description = null,
        public ?array $metadata = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            product_name: $data['product_name'],
            quantity: (int) ($data['quantity'] ?? 1),
            unit_price: (float) ($data['unit_price'] ?? 0),
            total_price: (float) ($data['total_price'] ?? 0),
            product_id: $data['product_id'] ?? null,
            description: $data['description'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    public static function collection(array $items): array
    {
        return array_map(self::from(...), $items);
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'description' => $this->description,
            'metadata' => $this->metadata,
        ];
    }
}
