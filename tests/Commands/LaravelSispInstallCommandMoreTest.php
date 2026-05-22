<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('publishes config and migrations with and without force', function (): void {
    $configPath = config_path('sisp.php');
    $migrationDirectory = database_path('migrations');

    withInstallCommandFsLock(function () use ($configPath, $migrationDirectory): void {
        withPublishedPathBackups([$configPath, $migrationDirectory], function () use ($configPath): void {
            expect(Artisan::call('vendor:publish', ['--tag' => 'sisp-config', '--force' => true]))->toBe(0)
                ->and($configPath)->toBeFile()
                ->and(file_get_contents($configPath))->toBe(file_get_contents(__DIR__.'/../../config/sisp.php'));

            file_put_contents($configPath, 'stale');

            expect(Artisan::call('vendor:publish', ['--tag' => 'sisp-config']))->toBe(0)
                ->and(file_get_contents($configPath))->toBe('stale')
                ->and(Artisan::call('vendor:publish', ['--tag' => 'sisp-config', '--force' => true]))->toBe(0)
                ->and(file_get_contents($configPath))->toBe(file_get_contents(__DIR__.'/../../config/sisp.php'));

            expect(Artisan::call('vendor:publish', ['--tag' => 'sisp-migrations', '--force' => true]))->toBe(0);

            $migrationPath = publishedSispMigrationPath();
            expect($migrationPath)->toBeString()
                ->and($migrationPath)->toBeFile()
                ->and(file_get_contents($migrationPath))->toBe(file_get_contents(__DIR__.'/../../database/migrations/create_laravel_sisp_table.php'));

            file_put_contents($migrationPath, 'stale');

            expect(Artisan::call('vendor:publish', ['--tag' => 'sisp-migrations']))->toBe(0)
                ->and(file_get_contents($migrationPath))->toBe('stale')
                ->and(Artisan::call('vendor:publish', ['--tag' => 'sisp-migrations', '--force' => true]))->toBe(0)
                ->and(file_get_contents($migrationPath))->toBe(file_get_contents(__DIR__.'/../../database/migrations/create_laravel_sisp_table.php'));
        });
    });
});

function publishedSispMigrationPath(): string
{
    $matches = glob(database_path('migrations/*create_laravel_sisp_table.php'));

    expect($matches)->not->toBeFalse()
        ->and($matches)->not->toBeEmpty();

    return reset($matches);
}
