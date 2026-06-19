<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $attemptsTable = config('sisp.tables.transaction_attempts', 'sisp_transaction_attempts');

        if (! Schema::hasTable($attemptsTable)) {
            return;
        }

        if (! Schema::hasColumn($attemptsTable, 'attempt_session')) {
            Schema::table($attemptsTable, function (Blueprint $table): void {
                $table->string('attempt_session')->nullable()->after('merchant_session');
            });
        }

        if (Schema::hasIndex($attemptsTable, ['merchant_session'], 'unique')) {
            Schema::table($attemptsTable, function (Blueprint $table): void {
                $table->dropUnique(['merchant_session']);
            });
        }

        if (Schema::hasIndex($attemptsTable, ['merchant_ref', 'merchant_session'], 'unique')) {
            Schema::table($attemptsTable, function (Blueprint $table): void {
                $table->dropUnique(['merchant_ref', 'merchant_session']);
            });
        }

        if (! Schema::hasIndex($attemptsTable, ['merchant_ref', 'merchant_session'])) {
            Schema::table($attemptsTable, function (Blueprint $table): void {
                $table->index(['merchant_ref', 'merchant_session']);
            });
        }

        if (! Schema::hasIndex($attemptsTable, ['attempt_session'])) {
            Schema::table($attemptsTable, function (Blueprint $table): void {
                $table->index('attempt_session');
            });
        }
    }

    public function down(): void
    {
        $attemptsTable = config('sisp.tables.transaction_attempts', 'sisp_transaction_attempts');

        if (! Schema::hasTable($attemptsTable) || ! Schema::hasColumn($attemptsTable, 'attempt_session')) {
            return;
        }

        if (Schema::hasIndex($attemptsTable, ['attempt_session'])) {
            Schema::table($attemptsTable, function (Blueprint $table): void {
                $table->dropIndex(['attempt_session']);
            });
        }

        Schema::table($attemptsTable, function (Blueprint $table): void {
            $table->dropColumn('attempt_session');
        });
    }
};
