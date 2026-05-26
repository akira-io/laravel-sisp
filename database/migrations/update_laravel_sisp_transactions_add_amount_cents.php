<?php

declare(strict_types=1);

use Akira\Sisp\Support\SispAmount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');

        if (! Schema::hasTable($transactionsTable) || Schema::hasColumn($transactionsTable, 'amount_cents')) {
            return;
        }

        Schema::table($transactionsTable, function (Blueprint $table): void {
            $table->bigInteger('amount_cents')->default(0)->after('amount');
        });

        DB::table($transactionsTable)
            ->select(['id', 'amount'])
            ->orderBy('id')
            ->chunkById(100, function ($transactions) use ($transactionsTable): void {
                foreach ($transactions as $transaction) {
                    DB::table($transactionsTable)
                        ->where('id', $transaction->id)
                        ->update(['amount_cents' => SispAmount::toCents($transaction->amount)]);
                }
            });
    }

    public function down(): void
    {
        $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');

        if (! Schema::hasTable($transactionsTable) || ! Schema::hasColumn($transactionsTable, 'amount_cents')) {
            return;
        }

        Schema::table($transactionsTable, function (Blueprint $table): void {
            $table->dropColumn('amount_cents');
        });
    }
};
