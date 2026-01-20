<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('sisp.tables.transactions', 'sisp_transactions');

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            if (! Schema::hasColumn($tableName, 'locale')) {
                $table->string('locale', 5)->default('pt')->after('customer_address');
            }

            if (! Schema::hasColumn($tableName, 'customer_postal_code')) {
                $table->string('customer_postal_code')->nullable()->after('customer_address');
            }
        });
    }

    public function down(): void
    {
        $tableName = config('sisp.tables.transactions', 'sisp_transactions');

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            if (Schema::hasColumn($tableName, 'customer_postal_code')) {
                $table->dropColumn('customer_postal_code');
            }

            if (Schema::hasColumn($tableName, 'locale')) {
                $table->dropColumn('locale');
            }
        });
    }
};
