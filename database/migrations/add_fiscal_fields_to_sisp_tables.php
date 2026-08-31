<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const array COLUMN_LENGTHS = [
        'customer_vat' => 20,
        'customer_tax_name' => 255,
        'customer_tax_entity_type' => 20,
        'customer_tax_address' => 255,
    ];

    public function up(): void
    {
        foreach ($this->tables() as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $missing = array_filter(
                self::COLUMN_LENGTHS,
                fn (int $length, string $column): bool => ! Schema::hasColumn($table, $column),
                ARRAY_FILTER_USE_BOTH
            );

            if ($missing === []) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($missing): void {
                foreach ($missing as $column => $length) {
                    $blueprint->string($column, $length)->nullable();
                }
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
                array_keys(self::COLUMN_LENGTHS),
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
};
