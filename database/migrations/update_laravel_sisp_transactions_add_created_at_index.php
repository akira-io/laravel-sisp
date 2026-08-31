<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');

        if (! Schema::hasTable($transactionsTable)) {
            return;
        }

        if ($this->hasIndex($transactionsTable)) {
            return;
        }

        $indexName = $this->indexName($transactionsTable);

        Schema::table($transactionsTable, function (Blueprint $table) use ($indexName): void {
            $table->index(['created_at'], $indexName);
        });
    }

    public function down(): void
    {
        $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');

        if (! Schema::hasTable($transactionsTable)) {
            return;
        }

        $indexName = $this->indexName($transactionsTable);

        if (! $this->hasIndex($transactionsTable, $indexName)) {
            return;
        }

        Schema::table($transactionsTable, function (Blueprint $table) use ($indexName): void {
            $table->dropIndex($indexName);
        });
    }

    private function indexName(string $table): string
    {
        return str_replace(['-', '.'], '_', mb_strtolower($table.'_created_at_index'));
    }

    private function hasIndex(string $table, ?string $name = null): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['columns'] !== ['created_at']) {
                continue;
            }

            if ($name === null || mb_strtolower((string) $index['name']) === $name) {
                return true;
            }
        }

        return false;
    }
};
