<?php

declare(strict_types=1);

namespace Akira\Sisp\Commands;

use Illuminate\Console\Command;
use Throwable;

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

        if (confirm('Do you want to publish the configuration file?')) {
            $this->publishConfig(confirm('Force overwrite if file already exists?', false));
        }

        if (confirm('Do you want to publish the migration files?')) {
            $this->publishMigrations(confirm('Force overwrite if files already exist?', false));
        }

        if ($stackType === 'inertia') {
            if (confirm('Do you want to publish the Inertia React components for customization?')) {
                $this->publishInertiaComponents(confirm('Force overwrite if files already exist?', false));
            }
        } elseif (confirm('Do you want to publish the Blade views?')) {
            $this->publishBladeViews(confirm('Force overwrite if files already exist?', false));
        }

        if (confirm('Do you want to run database migrations now?')) {
            $this->runMigrations();
        }

        note('Laravel SISP installation completed successfully!');

        if (confirm('Would you like to support the project by giving a star on GitHub?')) {
            note('Visit: https://github.com/akira-io/laravel-sisp');
        }

        info('Thank you for choosing Laravel SISP!');

        return self::SUCCESS;
    }

    protected function publishConfig(bool $force = false): void
    {
        $options = ['--tag' => 'sisp-config'];
        if ($force) {
            $options['--force'] = true;
        }
        try {
            spin(fn () => $this->callSilent('vendor:publish', $options), 'Publishing configuration file...');
            info('Configuration file published.');
        } catch (Throwable) {
            info('Skipping config publish (vendor:publish not available).');
        }
    }

    protected function publishMigrations(bool $force = false): void
    {
        $options = ['--tag' => 'sisp-migrations'];
        if ($force) {
            $options['--force'] = true;
        }
        try {
            spin(fn () => $this->callSilent('vendor:publish', $options), 'Publishing migration files...');
            info('Migration files published.');
        } catch (Throwable) {
            info('Skipping migration publish (vendor:publish not available).');
        }
    }

    protected function publishInertiaComponents(bool $force = false): void
    {
        $options = ['--tag' => 'sisp-inertia-components'];
        if ($force) {
            $options['--force'] = true;
        }
        try {
            spin(fn () => $this->callSilent('vendor:publish', $options), 'Publishing Inertia components...');
            info('Inertia components published.');
        } catch (Throwable) {
            info('Skipping Inertia components publish (vendor:publish not available).');
        }
    }

    protected function publishBladeViews(bool $force = false): void
    {
        $options = ['--tag' => 'sisp-views'];
        if ($force) {
            $options['--force'] = true;
        }
        try {
            spin(fn () => $this->callSilent('vendor:publish', $options), 'Publishing Blade views...');
            info('Blade views published.');
        } catch (Throwable) {
            info('Skipping Blade views publish (vendor:publish not available).');
        }
    }

    protected function publishVueComponents(bool $force = false): void
    {
        $options = ['--tag' => 'sisp-vue-components'];
        if ($force) {
            $options['--force'] = true;
        }
        try {
            spin(fn () => $this->callSilent('vendor:publish', $options), 'Publishing Vue components...');
            info('Vue components published.');
        } catch (Throwable) {
            info('Skipping Vue components publish (vendor:publish not available).');
        }
    }

    protected function publishAssets(bool $force = false): void
    {
        $options = ['--tag' => 'sisp-assets'];
        if ($force) {
            $options['--force'] = true;
        }
        try {
            spin(fn () => $this->callSilent('vendor:publish', $options), 'Publishing assets...');
            info('Assets published.');
        } catch (Throwable) {
            info('Skipping assets publish (vendor:publish not available).');
        }
    }

    protected function runMigrations(): void
    {
        spin(fn () => $this->call('migrate'), 'Running database migrations...');
        info('Database migration completed.');
    }

    private function detectStack(): string
    {

        $inertiaConfigPath = base_path('config/inertia.php');
        if (file_exists($inertiaConfigPath)) {
            return 'inertia';
        }

        $composerPath = base_path('composer.json');
        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);
            if (isset($composer['require']['inertiajs/inertia-laravel'])) {
                return 'inertia';
            }
        }

        if (file_exists(base_path('vite.config.ts')) || file_exists(base_path('vite.config.js'))) {
            $path = file_exists(base_path('vite.config.ts'))
                ? base_path('vite.config.ts')
                : base_path('vite.config.js');
            $viteConfig = @file_get_contents($path) ?: '';
            if (str_contains($viteConfig, 'react')) {
                return 'inertia';
            }
        }

        return 'blade';
    }
}
