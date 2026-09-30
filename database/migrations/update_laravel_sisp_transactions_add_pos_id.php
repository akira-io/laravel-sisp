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

        if (Schema::hasColumn($transactionsTable, 'pos_id')) {
            return;
        }

        Schema::table($transactionsTable, function (Blueprint $table): void {
            $table->string('pos_id', 64)->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');

        if (! Schema::hasTable($transactionsTable)) {
            return;
        }

        if (! Schema::hasColumn($transactionsTable, 'pos_id')) {
            return;
        }

        Schema::table($transactionsTable, function (Blueprint $table): void {
            $table->dropColumn('pos_id');
        });
    }
};
