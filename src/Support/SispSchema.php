<?php

declare(strict_types=1);

namespace Akira\Sisp\Support;

use Akira\Sisp\Models\Refund;
use Akira\Sisp\Models\Transaction;

final class SispSchema
{
    private const array OPTIONAL_TRANSACTION_COLUMNS = [
        'error_code',
        'error_message',
        'callback_raw_payload',
        'request_payload_pruned_at',
    ];

    /** @var array<int, string>|null */
    private ?array $transactionColumns = null;

    private ?bool $refundsTableExists = null;

    public function transactionsHaveColumn(string $column): bool
    {
        return in_array($column, $this->transactionColumns(), true);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function withoutMissingTransactionColumns(array $attributes): array
    {
        return array_filter(
            $attributes,
            fn (string $column): bool => ! in_array($column, self::OPTIONAL_TRANSACTION_COLUMNS, true)
                || $this->transactionsHaveColumn($column),
            ARRAY_FILTER_USE_KEY,
        );
    }

    public function hasRefundsTable(): bool
    {
        $refund = new Refund;

        return $this->refundsTableExists ??= $refund->getConnection()->getSchemaBuilder()->hasTable($refund->getTable());
    }

    /**
     * @return array<int, string>
     */
    private function transactionColumns(): array
    {
        $transaction = new Transaction;

        return $this->transactionColumns ??= $transaction->getConnection()->getSchemaBuilder()->getColumnListing($transaction->getTable());
    }
}
