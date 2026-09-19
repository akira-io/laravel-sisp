<?php

declare(strict_types=1);

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;

it('rejects a limit that is not a positive whole number', function (string $command, string $limit): void {
    config()->set('sisp.transaction_status.reconciliation_enabled', true);

    $this->artisan($command, ['--limit' => $limit])
        ->expectsOutput('The --limit option must be a whole number of at least 1.')
        ->assertFailed();
})->with([
    'sisp:prune-request-payloads',
    'sisp:expire-pending',
    'sisp:reconcile-pending',
    'sisp:regenerate-pdfs',
])->with(['abc', '0', '-1', '1.5']);

it('rejects a window that is not a whole number', function (string $command, string $message): void {
    config()->set('sisp.transaction_status.reconciliation_enabled', true);

    $this->artisan($command, ['--older-than' => 'abc'])
        ->expectsOutput($message)
        ->assertFailed();
})->with([
    ['sisp:prune-request-payloads', 'The --older-than option must be a whole number of days, zero or more.'],
    ['sisp:expire-pending', 'The --older-than option must be a whole number of days, at least 1.'],
    ['sisp:reconcile-pending', 'The --older-than option must be a whole number of minutes, at least 1.'],
]);

it('rejects a reconciliation window below one minute', function (string $window): void {
    config()->set('sisp.transaction_status.reconciliation_enabled', true);

    $this->artisan('sisp:reconcile-pending', ['--older-than' => $window])
        ->expectsOutput('The --older-than option must be a whole number of minutes, at least 1.')
        ->assertFailed();
})->with(['-10', '0']);

it('does not expire anything when the configured window is below one day', function (): void {
    config()->set('sisp.expire_pending_after_days', 0);
    $transaction = Transaction::factory()->create([
        'status' => 'pending',
        'message_type' => null,
        'created_at' => now(),
    ]);

    $this->artisan('sisp:expire-pending')
        ->expectsOutput('The sisp.expire_pending_after_days setting must be at least 1 day.')
        ->assertFailed();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::pending);
});
