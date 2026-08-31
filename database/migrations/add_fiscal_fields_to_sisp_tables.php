<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->tables() as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (Schema::hasColumn($table, 'customer_vat')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->string('customer_vat', 20)->nullable();
                $blueprint->string('customer_tax_name')->nullable();
                $blueprint->string('customer_tax_entity_type', 20)->nullable();
                $blueprint->string('customer_tax_address')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables() as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns = array_values(array_filter(
                $this->columns(),
                fn (string $column): bool => Schema::hasColumn($table, $column)
            ));

            if ($columns === []) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
                $blueprint->dropColumn($columns);
            });
        }
    }

    /**
     * @return array<int, string>
     */
    private function tables(): array
    {
        return [
            config('sisp.tables.transactions', 'sisp_transactions'),
            config('sisp.tables.invoices', 'sisp_invoices'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function columns(): array
    {
        return [
            'customer_vat',
            'customer_tax_name',
            'customer_tax_entity_type',
            'customer_tax_address',
        ];
    }
};
