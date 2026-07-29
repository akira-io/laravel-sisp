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

        Schema::table($transactionsTable, function (Blueprint $table): void {
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');

        if (! Schema::hasTable($transactionsTable) || ! $this->hasIndex($transactionsTable)) {
            return;
        }

        Schema::table($transactionsTable, function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
        });
    }

    private function hasIndex(string $table): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['columns'] === ['created_at']) {
                return true;
            }
        }

        return false;
    }
};
