<?php

declare(strict_types=1);

use Akira\Sisp\Support\SispSchema;
use Illuminate\Support\Facades\Schema;

it('keeps every attribute when the upgrade columns exist', function (): void {
    $attributes = ['status' => 'failed', 'error_code' => '3', 'error_message' => 'Refused', 'callback_raw_payload' => []];

    expect(resolve(SispSchema::class)->withoutMissingTransactionColumns($attributes))->toBe($attributes)
        ->and(resolve(SispSchema::class)->hasRefundsTable())->toBeTrue();
});

it('drops only the upgrade columns the table does not have', function (): void {
    Schema::table('sisp_transactions', function ($table): void {
        $table->dropColumn(['error_code', 'error_message']);
    });

    expect(resolve(SispSchema::class)->withoutMissingTransactionColumns([
        'status' => 'failed',
        'error_code' => '3',
        'error_message' => 'Refused',
        'callback_raw_payload' => [],
    ]))->toBe(['status' => 'failed', 'callback_raw_payload' => []]);
});

it('reads the schema once per scope', function (): void {
    $schema = resolve(SispSchema::class);

    expect($schema->transactionsHaveColumn('error_code'))->toBeTrue();

    Schema::table('sisp_transactions', function ($table): void {
        $table->dropColumn('error_code');
    });

    expect($schema->transactionsHaveColumn('error_code'))->toBeTrue()
        ->and(resolve(SispSchema::class))->toBe($schema);

    app()->forgetScopedInstances();

    expect(resolve(SispSchema::class)->transactionsHaveColumn('error_code'))->toBeFalse();
});
