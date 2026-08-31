<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

it('indexes created_at on the transactions table', function (): void {
    $columns = array_map(
        fn (array $index): array => $index['columns'],
        Schema::getIndexes(config('sisp.tables.transactions', 'sisp_transactions')),
    );

    expect($columns)->toContain(['created_at']);
});

it('leaves the index alone when it is already there', function (): void {
    $migration = require __DIR__.'/../../database/migrations/update_laravel_sisp_transactions_add_created_at_index.php';

    $migration->up();

    $matching = array_filter(
        Schema::getIndexes(config('sisp.tables.transactions', 'sisp_transactions')),
        fn (array $index): bool => $index['columns'] === ['created_at'],
    );

    expect($matching)->toHaveCount(1);
});

it('leaves a custom-named created_at index alone on rollback', function (): void {
    $table = config('sisp.tables.transactions', 'sisp_transactions');
    $migration = require __DIR__.'/../../database/migrations/update_laravel_sisp_transactions_add_created_at_index.php';

    Schema::table($table, function (Blueprint $blueprint): void {
        $blueprint->dropIndex(['created_at']);
    });

    Schema::table($table, function (Blueprint $blueprint): void {
        $blueprint->index(['created_at'], 'custom_created_at_idx');
    });

    $migration->down();

    $names = array_column(Schema::getIndexes($table), 'name');

    expect($names)->toContain('custom_created_at_idx');
});

it('drops the index it created on rollback', function (): void {
    $table = config('sisp.tables.transactions', 'sisp_transactions');
    $migration = require __DIR__.'/../../database/migrations/update_laravel_sisp_transactions_add_created_at_index.php';

    $migration->down();

    $columns = array_column(Schema::getIndexes($table), 'columns');

    expect($columns)->not->toContain(['created_at']);

    $migration->up();

    expect(array_column(Schema::getIndexes($table), 'columns'))->toContain(['created_at']);
});
