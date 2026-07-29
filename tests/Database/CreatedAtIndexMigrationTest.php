<?php

declare(strict_types=1);

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
