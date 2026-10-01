<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

function rejectsWideStringIndexes(): bool
{
    return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
}

it('indexes merchant_session alone instead of the four string columns', function (): void {
    expect(transactionIndexColumns())
        ->toContain(['merchant_session'])
        ->not->toContain(['merchant_ref', 'merchant_session', 'status', 'message_type']);
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
})->skip(rejectsWideStringIndexes(...), 'MySQL and MariaDB reject the wide index, so no install can hold it.');

it('keeps the wide index when nothing else would serve merchant_ref', function (): void {
    $table = config('sisp.tables.transactions', 'sisp_transactions');

    Schema::table($table, function (Blueprint $blueprint): void {
        $blueprint->dropUnique(['merchant_ref']);
        $blueprint->index(['merchant_ref', 'merchant_session', 'status', 'message_type'], 'sisp_wide_lookup_idx');
    });

    narrowLookupMigration()->up();

    expect(transactionIndexColumns())->toContain(['merchant_ref', 'merchant_session', 'status', 'message_type']);
})->skip(rejectsWideStringIndexes(...), 'MySQL and MariaDB reject the wide index, so no install can hold it.');

it('adds nothing on an install that never had the wide index', function (): void {
    $before = transactionIndexColumns();

    narrowLookupMigration()->up();

    expect(transactionIndexColumns())->toEqual($before);
});

it('leaves an untouched install alone on rollback', function (): void {
    $before = transactionIndexColumns();

    narrowLookupMigration()->down();

    expect(transactionIndexColumns())->toEqual($before);
});

it('puts the wide index back on the install it took it from', function (): void {
    $table = config('sisp.tables.transactions', 'sisp_transactions');

    Schema::table($table, function (Blueprint $blueprint): void {
        $blueprint->dropIndex(['merchant_session']);
        $blueprint->index(['merchant_ref', 'merchant_session', 'status', 'message_type'], 'sisp_wide_lookup_idx');
    });

    narrowLookupMigration()->up();
    narrowLookupMigration()->down();

    expect(transactionIndexColumns())
        ->toContain(['merchant_ref', 'merchant_session', 'status', 'message_type'])
        ->not->toContain(['merchant_session']);
})->skip(rejectsWideStringIndexes(...), 'MySQL and MariaDB reject the wide index, so no install can hold it.');

it('does nothing when the transactions table is missing', function (): void {
    $configured = config('sisp.tables.transactions', 'sisp_transactions');

    config()->set('sisp.tables.transactions', 'sisp_absent_transactions');

    narrowLookupMigration()->up();
    narrowLookupMigration()->down();

    config()->set('sisp.tables.transactions', $configured);

    expect(Schema::hasTable('sisp_absent_transactions'))->toBeFalse();
});

it('leaves the wide index single on rollback when it was never removed', function (): void {
    $table = config('sisp.tables.transactions', 'sisp_transactions');

    Schema::table($table, function (Blueprint $blueprint): void {
        $blueprint->dropUnique(['merchant_ref']);
        $blueprint->dropIndex(['merchant_session']);
        $blueprint->index(['merchant_ref', 'merchant_session', 'status', 'message_type'], 'sisp_wide_lookup_idx');
    });

    narrowLookupMigration()->up();
    narrowLookupMigration()->down();

    $wide = array_filter(
        transactionIndexColumns(),
        fn (array $columns): bool => $columns === ['merchant_ref', 'merchant_session', 'status', 'message_type'],
    );

    expect($wide)->toHaveCount(1);
})->skip(rejectsWideStringIndexes(...), 'MySQL and MariaDB reject the wide index, so no install can hold it.');
