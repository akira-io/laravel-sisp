<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const array WIDE_COLUMNS = ['merchant_ref', 'merchant_session', 'status', 'message_type'];

    private const array NARROW_COLUMNS = ['merchant_session'];

    public function up(): void
    {
        $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');

        if (! Schema::hasTable($transactionsTable)) {
            return;
        }

        if ($this->findIndex($transactionsTable, self::WIDE_COLUMNS) === null) {
            return;
        }

        $this->addNarrowIndex($transactionsTable);
        $this->dropWideIndex($transactionsTable);
    }

    public function down(): void
    {
        $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');

        if (! Schema::hasTable($transactionsTable)) {
            return;
        }

        $narrowIndex = $this->narrowIndexName($transactionsTable);

        if (! in_array($narrowIndex, $this->indexNames($transactionsTable), true)) {
            return;
        }

        $wideIndex = $this->findIndex($transactionsTable, self::WIDE_COLUMNS);

        Schema::table($transactionsTable, function (Blueprint $table) use ($narrowIndex, $wideIndex): void {
            $table->dropIndex($narrowIndex);

            if ($wideIndex === null) {
                $table->index(self::WIDE_COLUMNS);
            }
        });
    }

    private function addNarrowIndex(string $table): void
    {
        if ($this->findIndex($table, self::NARROW_COLUMNS) !== null) {
            return;
        }

        $narrowIndex = $this->narrowIndexName($table);

        Schema::table($table, function (Blueprint $blueprint) use ($narrowIndex): void {
            $blueprint->index(self::NARROW_COLUMNS, $narrowIndex);
        });
    }

    private function dropWideIndex(string $table): void
    {
        $wideIndex = $this->findIndex($table, self::WIDE_COLUMNS);

        if ($wideIndex === null || ! $this->hasMerchantReferenceIndex($table, $wideIndex)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($wideIndex): void {
            $blueprint->dropIndex($wideIndex);
        });
    }

    private function hasMerchantReferenceIndex(string $table, string $excluding): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ((string) $index['name'] === $excluding) {
                continue;
            }

            if (($index['columns'][0] ?? null) === 'merchant_ref') {
                return true;
            }
        }

        return false;
    }

    private function narrowIndexName(string $table): string
    {
        return str_replace(['-', '.'], '_', mb_strtolower($table.'_merchant_session_lookup_index'));
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

    /**
     * @param  list<string>  $columns
     */
    private function findIndex(string $table, array $columns): ?string
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['columns'] !== $columns) {
                continue;
            }

            if ($index['unique'] === true) {
                continue;
            }

            return (string) $index['name'];
        }

        return null;
    }
};
