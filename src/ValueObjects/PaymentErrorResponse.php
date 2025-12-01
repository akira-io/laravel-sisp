<?php

declare(strict_types=1);

namespace Akira\Sisp\ValueObjects;

final readonly class PaymentErrorResponse
{
    public function __construct(
        public string $code,
        public string $label,
        public string $category,
        public string $categoryLabel,
        public string $action,
        public string $actionLabel,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            label: $data['label'],
            category: $data['category'],
            categoryLabel: $data['categoryLabel'],
            action: $data['action'],
            actionLabel: $data['actionLabel'],
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'label' => $this->label,
            'category' => $this->category,
            'categoryLabel' => $this->categoryLabel,
            'action' => $this->action,
            'actionLabel' => $this->actionLabel,
        ];
    }
}
