<?php

declare(strict_types=1);

use Akira\Sisp\ValueObjects\PaymentErrorResponse;

it('creates instance from array', function (): void {
    $data = [
        'code' => '3',
        'label' => 'Insufficient Funds',
        'category' => 'funds',
        'categoryLabel' => 'Funds Issue',
        'action' => 'retry',
        'actionLabel' => 'Please retry with sufficient funds',
    ];

    $error = PaymentErrorResponse::fromArray($data);

    expect($error)->toBeInstanceOf(PaymentErrorResponse::class)
        ->and($error->code)->toBe('3')
        ->and($error->label)->toBe('Insufficient Funds')
        ->and($error->category)->toBe('funds')
        ->and($error->categoryLabel)->toBe('Funds Issue')
        ->and($error->action)->toBe('retry')
        ->and($error->actionLabel)->toBe('Please retry with sufficient funds');
});

it('converts to array', function (): void {
    $error = new PaymentErrorResponse(
        code: '12',
        label: 'Card Expired',
        category: 'card',
        categoryLabel: 'Card Issue',
        action: 'update_card',
        actionLabel: 'Please update your card',
    );

    $array = $error->toArray();

    expect($array)->toBeArray()
        ->toHaveKeys(['code', 'label', 'category', 'categoryLabel', 'action', 'actionLabel'])
        ->and($array['code'])->toBe('12')
        ->and($array['label'])->toBe('Card Expired');
});

it('maintains data integrity on array conversion', function (): void {
    $data = [
        'code' => '6',
        'label' => 'Declined',
        'category' => 'security',
        'categoryLabel' => 'Security Issue',
        'action' => 'contact_issuer',
        'actionLabel' => 'Please contact your bank',
    ];

    $error = PaymentErrorResponse::fromArray($data);
    $converted = $error->toArray();

    expect($converted)->toBe($data);
});

it('is readonly and prevents modifications', function (): void {
    $error = new PaymentErrorResponse(
        code: '3',
        label: 'Test',
        category: 'funds',
        categoryLabel: 'Test Category',
        action: 'test_action',
        actionLabel: 'Test Action Label',
    );

    expect($error->code)->toBe('3');
});
