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

        Schema::table($transactionsTable, function (Blueprint $table) use ($transactionsTable): void {
            if (! Schema::hasColumn($transactionsTable, 'callback_raw_payload')) {
                $table->longText('callback_raw_payload')->nullable()->after('payload');
            }

            if (! Schema::hasColumn($transactionsTable, 'error_code')) {
                $table->string('error_code', 4)->nullable()->after('message_type');
            }

            if (! Schema::hasColumn($transactionsTable, 'error_message')) {
                $table->string('error_message')->nullable()->after('error_code');
            }
        });
    }

    public function down(): void
    {
        $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');

        if (! Schema::hasTable($transactionsTable)) {
            return;
        }

        Schema::table($transactionsTable, function (Blueprint $table) use ($transactionsTable): void {
            $columns = array_values(array_filter(
                ['callback_raw_payload', 'error_code', 'error_message'],
                fn (string $column): bool => Schema::hasColumn($transactionsTable, $column)
            ));

            if ($columns === []) {
                return;
            }

            $table->dropColumn($columns);
        });
    }
};
