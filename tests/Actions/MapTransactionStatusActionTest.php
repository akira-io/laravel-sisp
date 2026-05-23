<?php

declare(strict_types=1);

use Akira\Sisp\Actions\Transaction\MapTransactionStatusAction;
use Akira\Sisp\Enums\TransactionStatus;

it('maps success message type to completed', function (): void {
    $map = resolve(MapTransactionStatusAction::class);
    expect($map->handle('8'))->toBe(TransactionStatus::completed)
        ->and($map->handle('10'))->toBe(TransactionStatus::completed)
        ->and($map->handle('M'))->toBe(TransactionStatus::completed)
        ->and($map->handle('P'))->toBe(TransactionStatus::completed);
});

it('maps error message type to failed', function (): void {
    $map = resolve(MapTransactionStatusAction::class);
    expect($map->handle('6'))->toBe(TransactionStatus::failed);
});

it('maps unknown message type to pending', function (): void {
    $map = resolve(MapTransactionStatusAction::class);
    expect($map->handle('X'))->toBe(TransactionStatus::pending);
});
