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

        if ($this->hasIndex($refundsTable)) {
            return;
        }

        $indexName = $this->indexName($refundsTable);

        Schema::table($refundsTable, function (Blueprint $table) use ($indexName): void {
            $table->unique(['transaction_id', 'idempotency_key'], $indexName);
        });
    }

    public function down(): void
    {
        $refundsTable = config('sisp.tables.refunds', 'sisp_refunds');

        if (! Schema::hasTable($refundsTable)) {
            return;
        }

        $indexName = $this->indexName($refundsTable);

        if ($this->hasIndex($refundsTable, $indexName)) {
            Schema::table($refundsTable, function (Blueprint $table) use ($indexName): void {
                $table->dropUnique($indexName);
            });
        }

        if (! Schema::hasColumn($refundsTable, 'idempotency_key')) {
            return;
        }

        Schema::table($refundsTable, function (Blueprint $table): void {
            $table->dropColumn('idempotency_key');
        });
    }

    private function indexName(string $table): string
    {
        return str_replace(['-', '.'], '_', mb_strtolower($table.'_transaction_id_idempotency_key_unique'));
    }

    private function hasIndex(string $table, ?string $name = null): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['columns'] !== ['transaction_id', 'idempotency_key'] || ! $index['unique']) {
                continue;
            }

            if ($name === null || mb_strtolower((string) $index['name']) === $name) {
                return true;
            }
        }

        return false;
    }
};
