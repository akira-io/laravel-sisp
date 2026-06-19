<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('backfills legacy duplicate identifiers without blocking the attempts migration', function (): void {
    $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');
    $attemptsTable = config('sisp.tables.transaction_attempts', 'sisp_transaction_attempts');
    $referencesTable = config('sisp.tables.transaction_references', 'sisp_transaction_references');
    $now = now();

    Schema::dropIfExists($attemptsTable);
    Schema::dropIfExists($referencesTable);

    DB::table($transactionsTable)->insert([
        [
            'merchant_ref' => 'MR-LEGACY-DUPLICATE',
            'merchant_session' => 'MS-LEGACY-DUPLICATE',
            'amount' => 100.0,
            'amount_cents' => 10000,
            'currency' => '132',
            'status' => 'pending',
            'transaction_code' => '1',
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'merchant_ref' => 'MR-LEGACY-DUPLICATE',
            'merchant_session' => 'MS-LEGACY-DUPLICATE',
            'amount' => 100.0,
            'amount_cents' => 10000,
            'currency' => '132',
            'status' => 'pending',
            'transaction_code' => '1',
            'created_at' => $now,
            'updated_at' => $now,
        ],
    ]);

    $migration = include __DIR__.'/../../database/migrations/create_sisp_transaction_attempts_table.php';
    $migration->up();

    $attempts = DB::table($attemptsTable)
        ->orderBy('transaction_id')
        ->get();

    expect(DB::table($referencesTable)->count())->toBe(1)
        ->and($attempts)->toHaveCount(2)
        ->and($attempts[0]->merchant_ref)->toBe('MR-LEGACY-DUPLICATE')
        ->and($attempts[0]->merchant_session)->toBe('MS-LEGACY-DUPLICATE')
        ->and($attempts[0]->attempt_session)->toBe('MS-LEGACY-DUPLICATE')
        ->and($attempts[0]->superseded_at)->toBeNull()
        ->and($attempts[1]->merchant_ref)->toBe('MR-LEGACY-DUPLICATE')
        ->and($attempts[1]->merchant_session)->toBe('MS-LEGACY-DUPLICATE')
        ->and($attempts[1]->attempt_session)->toContain('MS-LEGACY-DUPLICATE-legacy-')
        ->and($attempts[1]->superseded_at)->not->toBeNull();
});
