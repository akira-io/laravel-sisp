<?php

declare(strict_types=1);

use Akira\Sisp\Actions\Transaction\MapTransactionStatusAction;
use Akira\Sisp\Enums\TransactionStatus;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

it('completes the documented success pairs', function (string $messageType, string $merchantResponse): void {
    expect(resolve(MapTransactionStatusAction::class)->handle($messageType, $merchantResponse))
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
    expect(resolve(MapTransactionStatusAction::class)->handle('6', ''))
        ->toBe(TransactionStatus::failed);
});

it('does not complete a success type with an unexpected merchant response', function (): void {
    expect(resolve(MapTransactionStatusAction::class)->handle('8', '0'))
        ->toBe(TransactionStatus::pending);
});

it('leaves an empty message type pending for reconciliation', function (): void {
    expect(resolve(MapTransactionStatusAction::class)->handle('', ''))
        ->toBe(TransactionStatus::pending)
        ->and(resolve(MapTransactionStatusAction::class)->handle(null))
        ->toBe(TransactionStatus::pending);
});

it('no longer treats ISO-8583 codes as message types', function (string $messageType): void {
    expect(resolve(MapTransactionStatusAction::class)->handle($messageType, ''))
        ->toBe(TransactionStatus::pending);
})->with(['51', '33', '91', '99']);

it('logs warning when known success message type arrives with unexpected merchant response', function (): void {
    $handler = new TestHandler();
    $logger = new Logger('test', [$handler]);
    Log::swap($logger);

    $status = resolve(MapTransactionStatusAction::class)->handle('8', '0');

    expect($status)->toBe(TransactionStatus::pending)
        ->and($handler->getRecords())->toHaveLength(1)
        ->and($handler->getRecords()[0]['level_name'])->toBe('WARNING')
        ->and($handler->getRecords()[0]['message'])->toBe('SISP callback received known success message type with unexpected merchant response.')
        ->and($handler->getRecords()[0]['context'])->toMatchArray([
            'messageType' => '8',
            'merchantResponse' => '0',
            'expectedResponses' => 'C',
        ]);
});

it('does not log when expected merchant response arrives', function (): void {
    $handler = new TestHandler();
    $logger = new Logger('test', [$handler]);
    Log::swap($logger);

    $status = resolve(MapTransactionStatusAction::class)->handle('8', 'C');

    expect($status)->toBe(TransactionStatus::completed)
        ->and($handler->getRecords())->toHaveLength(0);
});

it('does not log when message type is empty', function (): void {
    $handler = new TestHandler();
    $logger = new Logger('test', [$handler]);
    Log::swap($logger);

    $status1 = resolve(MapTransactionStatusAction::class)->handle('', '');
    $status2 = resolve(MapTransactionStatusAction::class)->handle(null);

    expect($status1)->toBe(TransactionStatus::pending)
        ->and($status2)->toBe(TransactionStatus::pending)
        ->and($handler->getRecords())->toHaveLength(0);
});
