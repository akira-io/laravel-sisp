<?php

declare(strict_types=1);

use Akira\Sisp\ValueObjects\TransactionItemData;

it('creates instance with all fields', function (): void {
    $item = new TransactionItemData(
        product_name: 'Widget',
        quantity: 2,
        unit_price: 50.00,
        total_price: 100.00,
        product_id: 'PROD123',
        description: 'A great widget',
        metadata: ['color' => 'blue'],
    );

    expect($item)->toBeInstanceOf(TransactionItemData::class)
        ->and($item->product_name)->toBe('Widget')
        ->and($item->quantity)->toBe(2)
        ->and($item->unit_price)->toBe(50.00)
        ->and($item->total_price)->toBe(100.00)
        ->and($item->product_id)->toBe('PROD123')
        ->and($item->description)->toBe('A great widget')
        ->and($item->metadata)->toBe(['color' => 'blue']);
});

it('creates instance with only required fields', function (): void {
    $item = new TransactionItemData(
        product_name: 'Gadget',
        quantity: 1,
        unit_price: 25.50,
        total_price: 25.50,
    );

    expect($item->product_name)->toBe('Gadget')
        ->and($item->quantity)->toBe(1)
        ->and($item->unit_price)->toBe(25.50)
        ->and($item->total_price)->toBe(25.50)
        ->and($item->product_id)->toBeNull()
        ->and($item->description)->toBeNull()
        ->and($item->metadata)->toBeNull();
});

it('creates instance from array with all fields', function (): void {
    $data = [
        'product_name' => 'Service',
        'quantity' => 3,
        'unit_price' => 100.00,
        'total_price' => 300.00,
        'product_id' => 'SRV456',
        'description' => 'Premium service',
        'metadata' => ['type' => 'subscription'],
    ];

    $item = TransactionItemData::from($data);

    expect($item->product_name)->toBe('Service')
        ->and($item->quantity)->toBe(3)
        ->and($item->unit_price)->toBe(100.00)
        ->and($item->total_price)->toBe(300.00)
        ->and($item->product_id)->toBe('SRV456')
        ->and($item->description)->toBe('Premium service')
        ->and($item->metadata)->toBe(['type' => 'subscription']);
});

it('creates instance from array with missing optional fields', function (): void {
    $data = [
        'product_name' => 'Item',
        'unit_price' => 15.00,
        'total_price' => 15.00,
    ];

    $item = TransactionItemData::from($data);

    expect($item->product_name)->toBe('Item')
        ->and($item->quantity)->toBe(1)
        ->and($item->unit_price)->toBe(15.00)
        ->and($item->total_price)->toBe(15.00)
        ->and($item->product_id)->toBeNull()
        ->and($item->description)->toBeNull()
        ->and($item->metadata)->toBeNull();
});

it('uses default quantity of 1 when not provided', function (): void {
    $data = [
        'product_name' => 'Item',
        'unit_price' => 10.00,
        'total_price' => 10.00,
    ];

    $item = TransactionItemData::from($data);

    expect($item->quantity)->toBe(1);
});

it('uses default unit_price of 0 when not provided', function (): void {
    $data = [
        'product_name' => 'Free Item',
        'quantity' => 1,
        'total_price' => 0.00,
    ];

    $item = TransactionItemData::from($data);

    expect($item->unit_price)->toBe(0.0);
});

it('uses default total_price of 0 when not provided', function (): void {
    $data = [
        'product_name' => 'Free Item',
        'quantity' => 1,
        'unit_price' => 0.00,
    ];

    $item = TransactionItemData::from($data);

    expect($item->total_price)->toBe(0.0);
});

it('converts string values to correct types', function (): void {
    $data = [
        'product_name' => 'Item',
        'quantity' => '5',
        'unit_price' => '20.50',
        'total_price' => '102.50',
    ];

    $item = TransactionItemData::from($data);

    expect($item->quantity)->toBe(5)
        ->and($item->quantity)->toBeInt()
        ->and($item->unit_price)->toBe(20.50)
        ->and($item->unit_price)->toBeFloat()
        ->and($item->total_price)->toBe(102.50)
        ->and($item->total_price)->toBeFloat();
});

it('converts to array correctly', function (): void {
    $item = new TransactionItemData(
        product_name: 'Widget',
        quantity: 2,
        unit_price: 50.00,
        total_price: 100.00,
        product_id: 'PROD123',
        description: 'A great widget',
        metadata: ['color' => 'blue'],
    );

    $array = $item->toArray();

    expect($array)->toBeArray()
        ->toHaveKeys([
            'product_id',
            'product_name',
            'quantity',
            'unit_price',
            'total_price',
            'description',
            'metadata',
        ])
        ->and($array['product_name'])->toBe('Widget')
        ->and($array['quantity'])->toBe(2)
        ->and($array['unit_price'])->toBe(50.00)
        ->and($array['total_price'])->toBe(100.00)
        ->and($array['product_id'])->toBe('PROD123')
        ->and($array['description'])->toBe('A great widget')
        ->and($array['metadata'])->toBe(['color' => 'blue']);
});

it('converts to array with null optional fields', function (): void {
    $item = new TransactionItemData(
        product_name: 'Item',
        quantity: 1,
        unit_price: 10.00,
        total_price: 10.00,
    );

    $array = $item->toArray();

    expect($array['product_name'])->toBe('Item')
        ->and($array['quantity'])->toBe(1)
        ->and($array['product_id'])->toBeNull()
        ->and($array['description'])->toBeNull()
        ->and($array['metadata'])->toBeNull();
});

it('creates collection from array of items', function (): void {
    $items = [
        [
            'product_name' => 'Item 1',
            'quantity' => 1,
            'unit_price' => 10.00,
            'total_price' => 10.00,
        ],
        [
            'product_name' => 'Item 2',
            'quantity' => 2,
            'unit_price' => 20.00,
            'total_price' => 40.00,
        ],
        [
            'product_name' => 'Item 3',
            'quantity' => 3,
            'unit_price' => 30.00,
            'total_price' => 90.00,
        ],
    ];

    $collection = TransactionItemData::collection($items);

    expect($collection)->toBeArray()
        ->toHaveCount(3)
        ->and($collection[0])->toBeInstanceOf(TransactionItemData::class)
        ->and($collection[0]->product_name)->toBe('Item 1')
        ->and($collection[1]->product_name)->toBe('Item 2')
        ->and($collection[2]->product_name)->toBe('Item 3')
        ->and($collection[2]->quantity)->toBe(3);
});

it('creates empty collection from empty array', function (): void {
    $collection = TransactionItemData::collection([]);

    expect($collection)->toBeArray()
        ->toBeEmpty();
});

it('is readonly and immutable', function (): void {
    $item = new TransactionItemData(
        product_name: 'Widget',
        quantity: 2,
        unit_price: 50.00,
        total_price: 100.00,
    );

    expect($item->product_name)->toBe('Widget')
        ->and($item->quantity)->toBe(2);
});
