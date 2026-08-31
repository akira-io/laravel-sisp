<?php

declare(strict_types=1);

use Akira\Sisp\SispServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelPackageTools\Package;

function fiscalFieldsMigration(): object
{
    return require __DIR__.'/../../database/migrations/add_fiscal_fields_to_sisp_tables.php';
}

it('is published by the service provider', function (): void {
    $package = new Package();

    resolve(SispServiceProvider::class, ['app' => app()])->configurePackage($package);

    expect($package->migrationFileNames)->toContain('add_fiscal_fields_to_sisp_tables');
});

it('drops and restores the tax columns', function (): void {
    $migration = fiscalFieldsMigration();
    $transactions = config('sisp.tables.transactions', 'sisp_transactions');

    $migration->down();

    expect(Schema::hasColumn($transactions, 'customer_vat'))->toBeFalse();

    $migration->up();

    expect(Schema::hasColumns($transactions, [
        'customer_vat',
        'customer_tax_name',
        'customer_tax_entity_type',
        'customer_tax_address',
    ]))->toBeTrue();
});

it('rolls back twice without failing on already dropped columns', function (): void {
    $migration = fiscalFieldsMigration();

    $migration->down();
    $migration->down();

    expect(Schema::hasColumn(config('sisp.tables.invoices', 'sisp_invoices'), 'customer_vat'))->toBeFalse();

    $migration->up();
});

it('skips tables that do not exist yet', function (): void {
    config()->set('sisp.tables.transactions', 'sisp_transactions_absent');

    $migration = fiscalFieldsMigration();

    $migration->up();
    $migration->down();

    expect(Schema::hasTable('sisp_transactions_absent'))->toBeFalse();
});

it('completes a partially upgraded table', function (): void {
    $migration = fiscalFieldsMigration();
    $transactions = config('sisp.tables.transactions', 'sisp_transactions');

    Schema::table($transactions, function (Blueprint $blueprint): void {
        $blueprint->dropColumn(['customer_tax_name', 'customer_tax_entity_type', 'customer_tax_address']);
    });

    expect(Schema::hasColumn($transactions, 'customer_vat'))->toBeTrue()
        ->and(Schema::hasColumn($transactions, 'customer_tax_name'))->toBeFalse();

    $migration->up();

    expect(Schema::hasColumns($transactions, [
        'customer_vat',
        'customer_tax_name',
        'customer_tax_entity_type',
        'customer_tax_address',
    ]))->toBeTrue();
});
