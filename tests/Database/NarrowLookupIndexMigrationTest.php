<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

function narrowLookupMigration(): object
{
    return require __DIR__.'/../../database/migrations/update_laravel_sisp_transactions_narrow_lookup_index.php';
}

function transactionIndexColumns(): array
{
    return array_column(
        Schema::getIndexes(config('sisp.tables.transactions', 'sisp_transactions')),
        'columns',
    );
}

it('indexes merchant_session alone instead of the four string columns', function (): void {
    expect(transactionIndexColumns())
        ->toContain(['merchant_session'])
        ->not->toContain(['merchant_ref', 'merchant_session', 'status', 'message_type']);
});

it('keeps every index key under the InnoDB 3072-byte limit', function (): void {
    $lengths = [
        'merchant_ref' => 255,
        'merchant_session' => 255,
        'status' => 255,
        'message_type' => 255,
        'transaction_id' => 255,
        'customer_email' => 255,
        'created_at' => 8,
        'id' => 8,
    ];

    foreach (Schema::getIndexes(config('sisp.tables.transactions', 'sisp_transactions')) as $index) {
        $bytes = array_sum(array_map(
            fn (string $column): int => ($lengths[$column] ?? 255) * 4,
            $index['columns'],
        ));

        expect($bytes)->toBeLessThanOrEqual(3072, "index {$index['name']} needs {$bytes} bytes");
    }
});

it('replaces the wide index on an install that already has it', function (): void {
    $table = config('sisp.tables.transactions', 'sisp_transactions');

    Schema::table($table, function (Blueprint $blueprint): void {
        $blueprint->dropIndex(['merchant_session']);
        $blueprint->index(['merchant_ref', 'merchant_session', 'status', 'message_type'], 'sisp_wide_lookup_idx');
    });

    narrowLookupMigration()->up();

    expect(transactionIndexColumns())
        ->toContain(['merchant_session'])
        ->not->toContain(['merchant_ref', 'merchant_session', 'status', 'message_type']);
});

it('leaves the narrow index alone when it is already there', function (): void {
    narrowLookupMigration()->up();

    $matching = array_filter(
        transactionIndexColumns(),
        fn (array $columns): bool => $columns === ['merchant_session'],
    );

    expect($matching)->toHaveCount(1);
});

it('drops the narrow index on rollback and restores it on replay', function (): void {
    narrowLookupMigration()->down();

    expect(transactionIndexColumns())->not->toContain(['merchant_session']);

    narrowLookupMigration()->up();

    expect(transactionIndexColumns())->toContain(['merchant_session']);
});

it('does nothing when the transactions table is missing', function (): void {
    $configured = config('sisp.tables.transactions', 'sisp_transactions');

    config()->set('sisp.tables.transactions', 'sisp_absent_transactions');

    narrowLookupMigration()->up();
    narrowLookupMigration()->down();

    config()->set('sisp.tables.transactions', $configured);

    expect(Schema::hasTable('sisp_absent_transactions'))->toBeFalse();
});
