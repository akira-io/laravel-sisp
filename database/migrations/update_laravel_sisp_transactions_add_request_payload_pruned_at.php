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

        if (Schema::hasColumn($transactionsTable, 'request_payload_pruned_at')) {
            return;
        }

        Schema::table($transactionsTable, function (Blueprint $table): void {
            $table->timestamp('request_payload_pruned_at')->nullable()->after('payload');
        });
    }

    public function down(): void
    {
        $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');

        if (! Schema::hasTable($transactionsTable)) {
            return;
        }

        if (! Schema::hasColumn($transactionsTable, 'request_payload_pruned_at')) {
            return;
        }

        Schema::table($transactionsTable, function (Blueprint $table): void {
            $table->dropColumn('request_payload_pruned_at');
        });
    }
};
