<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionItem;
use Akira\Sisp\ValueObjects\TransactionItemData;
use Illuminate\Support\Facades\DB;

final readonly class StoreTransactionItemsAction
{
    public function handle(Transaction $transaction, TransactionItemData ...$items): void
    {
        if ($items === []) {
            return;
        }

        $records = array_map(
            fn (TransactionItemData $item): array => [
                'transaction_id' => $transaction->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price_cents' => (int) round($item->unit_price * 100),
                'total_price_cents' => (int) round($item->total_price * 100),
                'description' => $item->description,
                'metadata' => $item->metadata ? json_encode($item->metadata) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            $items
        );

        DB::table(new TransactionItem()->getTable())->insert($records);
    }
}
