<?php

declare(strict_types=1);

namespace Akira\Sisp\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;

final class LaravelSispInstallCommand extends Command
{
    protected $signature = 'sisp:install';

    protected $description = 'Install and configure the Laravel SISP package.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        info('Starting Laravel SISP installation...');

        $stackType = $this->detectStack();
        info("Detected stack: $stackType");

        // Step 1: Publish config
        if (confirm('Do you want to publish the configuration file?')) {
            $forcePublish = confirm('Force overwrite if file already exists?', false);

            $options = [
                '--tag' => 'sisp-config',
            ];

            if ($forcePublish) {
                $options['--force'] = true;
            }

            spin(fn () => $this->callSilent('vendor:publish', $options), 'Publishing configuration file...');

            info('Configuration file published.');
        }

        // Step 2: Publish migrations
        if (confirm('Do you want to publish the migration files?')) {
            $forceMigrations = confirm('Force overwrite if files already exist?', false);

            $options = [
                '--tag' => 'sisp-migrations',
            ];

            if ($forceMigrations) {
                $options['--force'] = true;
            }

            spin(fn () => $this->callSilent('vendor:publish', $options), 'Publishing migration files...');

            info('Migration files published.');
        }

        // Step 3: Publish stack-specific views
        if ($stackType === 'inertia') {
            if (confirm('Do you want to publish the Inertia React components for customization?')) {
                $forceInertia = confirm('Force overwrite if files already exist?', false);

                $options = [
                    '--tag' => 'sisp-inertia-components',
                ];

                if ($forceInertia) {
                    $options['--force'] = true;
                }

                spin(fn () => $this->callSilent('vendor:publish', $options), 'Publishing Inertia components...');

                info('Inertia components published.');
            }
        } elseif (confirm('Do you want to publish the Blade views?')) {
            $forceBlade = confirm('Force overwrite if files already exist?', false);
            $options = [
                '--tag' => 'sisp-views',
            ];
            if ($forceBlade) {
                $options['--force'] = true;
            }
            spin(fn () => $this->callSilent('vendor:publish', $options), 'Publishing Blade views...');
            info('Blade views published.');
        }

        // Step 4: Run migration
        if (confirm('Do you want to run database migrations now?')) {
            spin(fn () => $this->call('migrate'), 'Running database migrations...');
            info('Database migration completed.');
        }

        // Finish
        note('Laravel SISP installation completed successfully!');

        if (confirm('Would you like to support the project by giving a star on GitHub?')) {
            note('Visit: https://github.com/akira-io/laravel-sisp');
        }

        info('Thank you for choosing Laravel SISP!');

        return self::SUCCESS;
    }

    /**
     * Detect if the application is using Inertia or Blade stack.
     */
    private function detectStack(): string
    {
        // Check for Inertia configuration
        $inertiaConfigPath = base_path('config/inertia.php');
        if (file_exists($inertiaConfigPath)) {
            return 'inertia';
        }

        // Check for Inertia in composer.json
        $composerPath = base_path('composer.json');
        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);
            if (isset($composer['require']['inertiajs/inertia-laravel'])) {
                return 'inertia';
            }
        }

        // Check for Vite with React (Inertia indicator)
        if (file_exists(base_path('vite.config.ts')) || file_exists(base_path('vite.config.js'))) {
            $path = file_exists(base_path('vite.config.ts'))
                ? base_path('vite.config.ts')
                : base_path('vite.config.js');
            $viteConfig = @file_get_contents($path) ?: '';
            if (str_contains($viteConfig, 'react')) {
                return 'inertia';
            }
        }

        // Default to blade if no Inertia found
        return 'blade';
    }
}
