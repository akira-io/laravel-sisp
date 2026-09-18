<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

function refundIdempotencyIndexes(): array
{
    return array_values(array_filter(
        Schema::getIndexes(config('sisp.tables.refunds', 'sisp_refunds')),
        fn (array $index): bool => $index['columns'] === ['transaction_id', 'idempotency_key'],
    ));
}

it('adds a nullable idempotency key with a unique index per transaction', function (): void {
    $indexes = refundIdempotencyIndexes();

    expect(Schema::hasColumn(config('sisp.tables.refunds', 'sisp_refunds'), 'idempotency_key'))->toBeTrue()
        ->and($indexes)->toHaveCount(1)
        ->and($indexes[0]['unique'])->toBeTrue()
        ->and($indexes[0]['name'])->toBe('sisp_refunds_transaction_id_idempotency_key_unique');
});

it('leaves the column and index alone when they are already there', function (): void {
    $migration = require __DIR__.'/../../database/migrations/update_sisp_refunds_add_idempotency_key.php';

    $migration->up();

    expect(refundIdempotencyIndexes())->toHaveCount(1);
});

it('drops the index and the column on rollback', function (): void {
    $migration = require __DIR__.'/../../database/migrations/update_sisp_refunds_add_idempotency_key.php';

    $migration->down();

    expect(refundIdempotencyIndexes())->toBeEmpty()
        ->and(Schema::hasColumn(config('sisp.tables.refunds', 'sisp_refunds'), 'idempotency_key'))->toBeFalse();
});
