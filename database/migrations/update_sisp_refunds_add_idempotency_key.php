<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $refundsTable = config('sisp.tables.refunds', 'sisp_refunds');

        if (! Schema::hasTable($refundsTable)) {
            return;
        }

        if (! Schema::hasColumn($refundsTable, 'idempotency_key')) {
            Schema::table($refundsTable, function (Blueprint $table): void {
                $table->string('idempotency_key')->nullable()->after('reason');
            });
        }

        $this->addTransactionIndex($refundsTable);

        if ($this->uniqueKeyIndex($refundsTable) !== null) {
            return;
        }

        $uniqueIndex = $this->indexName($refundsTable, 'transaction_id_idempotency_key_unique');

        Schema::table($refundsTable, function (Blueprint $table) use ($uniqueIndex): void {
            $table->unique(['transaction_id', 'idempotency_key'], $uniqueIndex);
        });
    }

    public function down(): void
    {
        $refundsTable = config('sisp.tables.refunds', 'sisp_refunds');

        if (! Schema::hasTable($refundsTable)) {
            return;
        }

        $uniqueIndex = $this->uniqueKeyIndex($refundsTable);

        if ($uniqueIndex !== null) {
            $this->addTransactionIndex($refundsTable);

            Schema::table($refundsTable, function (Blueprint $table) use ($uniqueIndex): void {
                $table->dropUnique($uniqueIndex);
            });
        }

        if (! Schema::hasColumn($refundsTable, 'idempotency_key')) {
            return;
        }

        Schema::table($refundsTable, function (Blueprint $table): void {
            $table->dropColumn('idempotency_key');
        });
    }

    private function addTransactionIndex(string $table): void
    {
        $transactionIndex = $this->indexName($table, 'transaction_id_index');

        if (in_array($transactionIndex, $this->indexNames($table), true)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($transactionIndex): void {
            $blueprint->index(['transaction_id'], $transactionIndex);
        });
    }

    /**
     * @return array<int, string>
     */
    private function indexNames(string $table): array
    {
        return array_map(
            fn (array $index): string => mb_strtolower((string) $index['name']),
            Schema::getIndexes($table),
        );
    }

    private function indexName(string $table, string $suffix): string
    {
        return str_replace(['-', '.'], '_', mb_strtolower($table.'_'.$suffix));
    }

    private function uniqueKeyIndex(string $table): ?string
    {
        return $this->indexNamed($table, ['transaction_id', 'idempotency_key'], unique: true);
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function indexNamed(string $table, array $columns, bool $unique = false): ?string
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['columns'] === $columns && (! $unique || $index['unique'])) {
                return (string) $index['name'];
            }
        }

        return null;
    }
};
