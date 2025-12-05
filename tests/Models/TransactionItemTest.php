<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionItem;

it('casts unit and total price via accessors/mutators', function (): void {
    $t = Transaction::factory()->create();

    $item = new TransactionItem([
        'product_name' => 'X',
        'quantity' => 1,
    ]);
    $item->transaction_id = $t->id;
    $item->unit_price = 12.34; // triggers mutator
    $item->total_price = 12.34; // triggers mutator
    $item->save();

    $fresh = TransactionItem::query()->findOrFail($item->id);
    expect($fresh->unit_price_cents)->toBe(1234)
        ->and($fresh->total_price_cents)->toBe(1234)
        ->and($fresh->unit_price)->toBe(12.34)
        ->and($fresh->total_price)->toBe(12.34);
});

