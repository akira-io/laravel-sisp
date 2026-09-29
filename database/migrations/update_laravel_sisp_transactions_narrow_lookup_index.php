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

        $this->addNarrowIndex($transactionsTable);
        $this->dropWideIndex($transactionsTable);
    }

    public function down(): void
    {
        $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');

        if (! Schema::hasTable($transactionsTable)) {
            return;
        }

        $narrowIndex = $this->findIndex($transactionsTable, self::NARROW_COLUMNS);

        if ($narrowIndex === null) {
            return;
        }

        Schema::table($transactionsTable, function (Blueprint $table) use ($narrowIndex): void {
            $table->dropIndex($narrowIndex);
        });
    }

    private function addNarrowIndex(string $table): void
    {
        if ($this->findIndex($table, self::NARROW_COLUMNS) !== null) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->index(self::NARROW_COLUMNS);
        });
    }

    private function dropWideIndex(string $table): void
    {
        $wideIndex = $this->findIndex($table, self::WIDE_COLUMNS);

        if ($wideIndex === null) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($wideIndex): void {
            $blueprint->dropIndex($wideIndex);
        });
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
