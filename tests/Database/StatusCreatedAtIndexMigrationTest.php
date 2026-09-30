<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function statusCreatedAtMigration(): object
{
    return require __DIR__.'/../../database/migrations/update_laravel_sisp_transactions_add_status_created_at_index.php';
}

function statusCreatedAtIndexes(?string $table = null): array
{
    return array_filter(
        Schema::getIndexes($table ?? config('sisp.tables.transactions', 'sisp_transactions')),
        fn (array $index): bool => $index['columns'] === ['status', 'created_at'],
    );
}

function onPostgres(): bool
{
    return DB::connection()->getDriverName() === 'pgsql';
}

it('indexes status and created_at on the transactions table', function (): void {
    expect(statusCreatedAtIndexes())->toHaveCount(1);
});

it('runs outside a transaction so PostgreSQL can build the index concurrently', function (): void {
    expect(statusCreatedAtMigration()->withinTransaction)->toBeFalse();
});

it('leaves the index alone when it is already there', function (): void {
    statusCreatedAtMigration()->up();

    expect(statusCreatedAtIndexes())->toHaveCount(1);
});

it('drops the index it created on rollback and builds it again', function (): void {
    $migration = statusCreatedAtMigration();

    $migration->down();

    expect(statusCreatedAtIndexes())->toBeEmpty();

    $migration->up();

    expect(statusCreatedAtIndexes())->toHaveCount(1);
});

it('leaves a custom-named status/created_at index alone on rollback', function (): void {
    $table = config('sisp.tables.transactions', 'sisp_transactions');

    statusCreatedAtMigration()->down();

    Schema::table($table, function (Blueprint $blueprint): void {
        $blueprint->index(['status', 'created_at'], 'custom_status_created_at_idx');
    });

    statusCreatedAtMigration()->down();

    expect(array_column(Schema::getIndexes($table), 'name'))->toContain('custom_status_created_at_idx');

    Schema::table($table, function (Blueprint $blueprint): void {
        $blueprint->dropIndex('custom_status_created_at_idx');
    });
    statusCreatedAtMigration()->up();
});

it('rebuilds an index a failed concurrent build left invalid', function (): void {
    $table = config('sisp.tables.transactions', 'sisp_transactions');
    $indexName = $table.'_status_created_at_index';

    DB::statement('UPDATE pg_index SET indisvalid = false WHERE indexrelid = ?::regclass', [$indexName]);

    expect(DB::selectOne('SELECT indisvalid FROM pg_index WHERE indexrelid = ?::regclass', [$indexName])->indisvalid)->toBeFalse();

    statusCreatedAtMigration()->up();

    expect(DB::selectOne('SELECT indisvalid FROM pg_index WHERE indexrelid = ?::regclass', [$indexName])->indisvalid)->toBeTrue()
        ->and(statusCreatedAtIndexes())->toHaveCount(1);
})->skip(fn (): bool => ! onPostgres(), 'Only PostgreSQL builds the index concurrently.');

it('leaves an invalid index of the same name on another table alone', function (): void {
    $table = config('sisp.tables.transactions', 'sisp_transactions');
    $indexName = $table.'_status_created_at_index';

    Schema::create('sisp_other_rows', function (Blueprint $blueprint): void {
        $blueprint->id();
        $blueprint->string('status');
        $blueprint->timestamps();
    });

    statusCreatedAtMigration()->down();
    DB::statement(sprintf('CREATE INDEX %s ON sisp_other_rows (status, created_at)', DB::connection()->getSchemaGrammar()->wrap($indexName)));
    DB::statement('UPDATE pg_index SET indisvalid = false WHERE indexrelid = ?::regclass', [$indexName]);

    statusCreatedAtMigration()->up();

    expect(DB::selectOne('SELECT 1 AS present FROM pg_index WHERE indexrelid = to_regclass(?)', [$indexName]))->not->toBeNull()
        ->and(array_column(Schema::getIndexes('sisp_other_rows'), 'name'))->toContain($indexName);

    DB::statement(sprintf('DROP INDEX %s', DB::connection()->getSchemaGrammar()->wrap($indexName)));
    Schema::drop('sisp_other_rows');
    statusCreatedAtMigration()->up();
})->skip(fn (): bool => ! onPostgres(), 'Only PostgreSQL builds the index concurrently.');
