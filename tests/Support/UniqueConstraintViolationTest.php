<?php

declare(strict_types=1);

use Akira\Sisp\Support\UniqueConstraintViolation;
use Illuminate\Database\QueryException;

it('detects unique constraint violations without masking other integrity errors', function (string $sqlState, int|string|null $driverCode, string $message, bool $expected): void {
    expect(UniqueConstraintViolation::causedBy(
        unique_constraint_violation_query_exception($sqlState, $driverCode, $message)
    ))->toBe($expected);
})->with([
    'mysql unique duplicate entry' => ['23000', 1062, "Duplicate entry 'R1' for key 'sisp_transactions_merchant_ref_unique'", true],
    'postgres unique violation' => ['23505', 7, 'duplicate key value violates unique constraint "sisp_transactions_merchant_ref_unique"', true],
    'sqlite unique constraint failed' => ['23000', 19, 'UNIQUE constraint failed: sisp_transactions.merchant_ref', true],
    'mysql foreign key violation' => ['23000', 1452, 'Cannot add or update a child row: a foreign key constraint fails', false],
    'generic duplicate wording' => ['HY000', 1, 'Duplicate packet received from upstream service', false],
]);

function unique_constraint_violation_query_exception(string $sqlState, int|string|null $driverCode, string $message): QueryException
{
    $previous = new PDOException($message);
    $previous->errorInfo = [$sqlState, $driverCode, $message];

    return new QueryException('testing', 'insert into sisp_transactions values (?)', [], $previous);
}
