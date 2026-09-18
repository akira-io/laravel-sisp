<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

function refundsTableIndexes(array $columns): array
{
    return array_values(array_filter(
        Schema::getIndexes(config('sisp.tables.refunds', 'sisp_refunds')),
        fn (array $index): bool => $index['columns'] === $columns,
    ));
}

function refundIdempotencyMigration(): object
{
    return require __DIR__.'/../../database/migrations/update_sisp_refunds_add_idempotency_key.php';
}

it('adds a nullable idempotency key with a unique index per transaction', function (): void {
    $indexes = refundsTableIndexes(['transaction_id', 'idempotency_key']);

    expect(Schema::hasColumn(config('sisp.tables.refunds', 'sisp_refunds'), 'idempotency_key'))->toBeTrue()
        ->and($indexes)->toHaveCount(1)
        ->and($indexes[0]['unique'])->toBeTrue()
        ->and($indexes[0]['name'])->toBe('sisp_refunds_transaction_id_idempotency_key_unique');
});

it('gives the transaction foreign key an index of its own', function (): void {
    expect(refundsTableIndexes(['transaction_id']))->toHaveCount(1);
});

it('leaves the column and indexes alone when they are already there', function (): void {
    refundIdempotencyMigration()->up();

    expect(refundsTableIndexes(['transaction_id', 'idempotency_key']))->toHaveCount(1)
        ->and(refundsTableIndexes(['transaction_id']))->toHaveCount(1);
});

it('adds the unique index when a previous run stopped after the column', function (): void {
    $table = config('sisp.tables.refunds', 'sisp_refunds');

    Schema::table($table, function (Blueprint $blueprint): void {
        $blueprint->dropUnique('sisp_refunds_transaction_id_idempotency_key_unique');
    });

    refundIdempotencyMigration()->up();

    expect(refundsTableIndexes(['transaction_id', 'idempotency_key']))->toHaveCount(1);
});

it('drops the unique index and the column on rollback and keeps the foreign key index', function (): void {
    refundIdempotencyMigration()->down();

    expect(refundsTableIndexes(['transaction_id', 'idempotency_key']))->toBeEmpty()
        ->and(refundsTableIndexes(['transaction_id']))->toHaveCount(1)
        ->and(Schema::hasColumn(config('sisp.tables.refunds', 'sisp_refunds'), 'idempotency_key'))->toBeFalse();
});

it('drops a unique index created under another name on rollback', function (): void {
    $table = config('sisp.tables.refunds', 'sisp_refunds');

    Schema::table($table, function (Blueprint $blueprint): void {
        $blueprint->dropUnique('sisp_refunds_transaction_id_idempotency_key_unique');
        $blueprint->unique(['transaction_id', 'idempotency_key'], 'custom_refund_key_unique');
    });

    refundIdempotencyMigration()->down();

    expect(refundsTableIndexes(['transaction_id', 'idempotency_key']))->toBeEmpty()
        ->and(Schema::hasColumn($table, 'idempotency_key'))->toBeFalse();
});

it('does nothing when the refunds table does not exist', function (): void {
    config()->set('sisp.tables.refunds', 'missing_refunds');

    refundIdempotencyMigration()->up();
    refundIdempotencyMigration()->down();

    expect(Schema::hasTable('missing_refunds'))->toBeFalse();
});
