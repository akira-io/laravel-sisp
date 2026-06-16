<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;

it('recognizes existing merchant reference index errors across supported drivers', function (string $sqlState, int|string|null $driverCode, string $message, bool $expected): void {
    $migration = require __DIR__.'/../../database/migrations/create_sisp_transaction_attempts_table.php';
    $method = new ReflectionMethod($migration, 'indexAlreadyExists');
    $exception = transaction_attempts_migration_query_exception($sqlState, $driverCode, $message);

    expect($method->invoke($migration, $exception))->toBe($expected);
})->with([
    'mysql duplicate key name code' => ['42000', 1061, "Duplicate key name 'sisp_transactions_merchant_ref_unique'", true],
    'mysql duplicate key name message' => ['42000', 1064, "Duplicate key name 'sisp_transactions_merchant_ref_unique'", true],
    'postgres duplicate relation' => ['42P07', 7, 'relation "sisp_transactions_merchant_ref_unique" already exists', true],
    'sqlite index already exists' => ['HY000', 1, 'index sisp_transactions_merchant_ref_unique already exists', true],
    'mysql syntax error' => ['42000', 1064, 'You have an error in your SQL syntax', false],
]);

function transaction_attempts_migration_query_exception(string $sqlState, int|string|null $driverCode, string $message): QueryException
{
    $previous = new PDOException($message);
    $previous->errorInfo = [$sqlState, $driverCode, $message];

    return new QueryException('testing', 'alter table sisp_transactions add unique index', [], $previous);
}
