<?php

declare(strict_types=1);

use Akira\Sisp\Actions\Transaction\MapTransactionStatusAction;
use Akira\Sisp\Enums\TransactionStatus;

it('completes the documented success pairs', function (string $messageType, string $merchantResponse): void {
    expect(app(MapTransactionStatusAction::class)->handle($messageType, $merchantResponse))
        ->toBe(TransactionStatus::completed);
})->with([
    ['8', 'C'],
    ['P', 'C'],
    ['M', 'C'],
    ['A', '0'],
    ['B', '0'],
    ['C', '0'],
    ['10', ''],
    ['?', ''],
]);

it('fails on the documented error message type', function (): void {
    expect(app(MapTransactionStatusAction::class)->handle('6', ''))
        ->toBe(TransactionStatus::failed);
});

it('does not complete a success type with an unexpected merchant response', function (): void {
    expect(app(MapTransactionStatusAction::class)->handle('8', '0'))
        ->toBe(TransactionStatus::pending);
});

it('leaves an empty message type pending for reconciliation', function (): void {
    expect(app(MapTransactionStatusAction::class)->handle('', ''))
        ->toBe(TransactionStatus::pending)
        ->and(app(MapTransactionStatusAction::class)->handle(null))
        ->toBe(TransactionStatus::pending);
});

it('no longer treats ISO-8583 codes as message types', function (string $messageType): void {
    expect(app(MapTransactionStatusAction::class)->handle($messageType, ''))
        ->toBe(TransactionStatus::pending);
})->with(['51', '33', '91', '99']);
