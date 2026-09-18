<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Concerns;

use Akira\Sisp\Enums\InvoiceStatus;
use Akira\Sisp\Enums\SuccessMessageType;
use Akira\Sisp\Enums\TransactionCode;
use Akira\Sisp\Enums\TransactionStatus;
use BackedEnum;

trait DescribesEnums
{
    private const array ENUMS = [
        'transaction_status' => TransactionStatus::class,
        'transaction_code' => TransactionCode::class,
        'invoice_status' => InvoiceStatus::class,
        'success_message' => SuccessMessageType::class,
    ];

    /**
     * @param  class-string<BackedEnum>  $enumClass
     * @return array<int, array<string, mixed>>
     */
    private function describeEnum(string $enumClass): array
    {
        return collect($enumClass::cases())
            ->map(fn (BackedEnum $case): array => [
                'name' => $case->name,
                'value' => $case->value,
                ...(method_exists($case, 'label') ? ['label' => $case->label()] : []),
            ])
            ->all();
    }
}
