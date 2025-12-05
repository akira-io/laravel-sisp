<?php

declare(strict_types=1);

use Akira\Sisp\Actions\StoreTransactionItemsAction;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionItem;
use Akira\Sisp\ValueObjects\TransactionItemData;

it('stores transaction items with converted cents', function (): void {
    $t = Transaction::factory()->create();

    $action = resolve(StoreTransactionItemsAction::class);

    $i1 = TransactionItemData::from([
        'product_id' => 'SKU1',
        'product_name' => 'Product 1',
        'quantity' => 2,
        'unit_price' => 9.99,
        'total_price' => 19.98,
    ]);

    $i2 = TransactionItemData::from([
        'product_id' => 'SKU2',
        'product_name' => 'Product 2',
        'quantity' => 1,
        'unit_price' => 5.50,
        'total_price' => 5.50,
    ]);

    $action->handle($t, $i1, $i2);

    $items = TransactionItem::query()->where('transaction_id', $t->id)->orderBy('id')->get();
    expect($items)->toHaveCount(2)
        ->and($items[0]->unit_price_cents)->toBe(999)
        ->and($items[0]->total_price_cents)->toBe(1998)
        ->and($items[1]->unit_price_cents)->toBe(550)
        ->and($items[1]->total_price_cents)->toBe(550);
});

it('does nothing when no items provided', function (): void {
    $t = Transaction::factory()->create();

    $action = resolve(StoreTransactionItemsAction::class);

    // Call with no variadic items
    $action->handle($t);

    $count = TransactionItem::query()->where('transaction_id', $t->id)->count();
    expect($count)->toBe(0);
});
