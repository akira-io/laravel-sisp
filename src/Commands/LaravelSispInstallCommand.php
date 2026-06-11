<?php

declare(strict_types=1);

namespace Akira\Sisp\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;

#[Signature('sisp:install')]
#[Description('Install and configure the Laravel SISP package.')]
final class LaravelSispInstallCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        info('Starting Laravel SISP installation...');

        $stackType = $this->detectStack();
        info("Detected stack: $stackType");

        if ($this->askToggle('publish_config', 'Do you want to publish the configuration file?')) {
            $this->publishConfig($this->askToggle('force_config', 'Force overwrite if file already exists?', false));
        }

        if ($this->askToggle('publish_migrations', 'Do you want to publish the migration files?')) {
            $this->publishMigrations($this->askToggle('force_migrations', 'Force overwrite if files already exist?', false));
        }

        if ($stackType === 'inertia') {
            /* @codeCoverageIgnoreStart */
            if ($this->askToggle('publish_inertia', 'Do you want to publish the Inertia React components for customization?')) { // @codeCoverageIgnore
                $this->publishInertiaComponents($this->askToggle('force_inertia', 'Force overwrite if files already exist?', false)); // @codeCoverageIgnore
            }
            /* @codeCoverageIgnoreEnd */
        } elseif ($this->askToggle('publish_blade', 'Do you want to publish the Blade views?')) {
            /* @codeCoverageIgnoreStart */
            $this->publishBladeViews($this->askToggle('force_blade', 'Force overwrite if files already exist?', false)); // @codeCoverageIgnore
            /* @codeCoverageIgnoreEnd */
        }

        if ($this->askToggle('run_migrations', 'Do you want to run database migrations now?')) {
            $this->runMigrations();
        }

        note('Laravel SISP installation completed successfully!');

        if ($this->askToggle('give_star', 'Would you like to support the project by giving a star on GitHub?')) {
            note('Visit: https://github.com/akira-io/laravel-sisp');
        }

        info('Thank you for choosing Laravel SISP!');

        return self::SUCCESS;
    }

    /** @codeCoverageIgnore */
    private function publishConfig(bool $force = false): void
    {
        if (app()->runningUnitTests()) {
            info('Skipping config publish (test environment).');

            return;
        }

        /* @codeCoverageIgnoreStart */
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
        /* @codeCoverageIgnoreEnd */
    }

    /** @codeCoverageIgnore */
    private function publishMigrations(bool $force = false): void
    {
        if (app()->runningUnitTests()) {
            info('Skipping migration publish (test environment).');

            return;
        }

        /* @codeCoverageIgnoreStart */
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
        /* @codeCoverageIgnoreEnd */
    }

    /** @codeCoverageIgnore */
    private function publishInertiaComponents(bool $force = false): void
    {
        if (app()->runningUnitTests()) {
            info('Skipping Inertia components publish (test environment).');

            return;
        }

        /* @codeCoverageIgnoreStart */
        $options = ['--tag' => 'sisp-inertia-components'];
        if ($force) {
            $options['--force'] = true;
        }
        try {
            spin(fn () => $this->callSilent('vendor:publish', $options), 'Publishing Inertia components...');
            info('Inertia components published.'); // @codeCoverageIgnore
        } catch (Throwable) {
            info('Skipping Inertia components publish (vendor:publish not available).');
        }
        /* @codeCoverageIgnoreEnd */
    }

    /** @codeCoverageIgnore */
    private function publishBladeViews(bool $force = false): void
    {
        if (app()->runningUnitTests()) {
            info('Skipping Blade views publish (test environment).');

            return;
        }

        /* @codeCoverageIgnoreStart */
        $options = ['--tag' => 'sisp-views'];
        if ($force) {
            $options['--force'] = true;
        }
        try {
            spin(fn () => $this->callSilent('vendor:publish', $options), 'Publishing Blade views...');
            info('Blade views published.'); // @codeCoverageIgnore
        } catch (Throwable) {
            info('Skipping Blade views publish (vendor:publish not available).');
        }
        /* @codeCoverageIgnoreEnd */
    }

    /** @codeCoverageIgnore */
    private function publishVueComponents(bool $force = false): void
    {
        if (app()->runningUnitTests()) {
            info('Skipping Vue components publish (test environment).');

            return;
        }

        /* @codeCoverageIgnoreStart */
        $options = ['--tag' => 'sisp-vue-components'];
        if ($force) {
            $options['--force'] = true;
        }
        try {
            spin(fn () => $this->callSilent('vendor:publish', $options), 'Publishing Vue components...');
            info('Vue components published.'); // @codeCoverageIgnore
        } catch (Throwable) {
            info('Skipping Vue components publish (vendor:publish not available).');
        }
        /* @codeCoverageIgnoreEnd */
    }

    /** @codeCoverageIgnore */
    private function publishAssets(bool $force = false): void
    {
        if (app()->runningUnitTests()) {
            info('Skipping assets publish (test environment).');

            return;
        }

        /* @codeCoverageIgnoreStart */
        $options = ['--tag' => 'sisp-assets'];
        if ($force) {
            $options['--force'] = true;
        }
        try {
            spin(fn () => $this->callSilent('vendor:publish', $options), 'Publishing assets...');
            info('Assets published.'); // @codeCoverageIgnore
        } catch (Throwable) {
            info('Skipping assets publish (vendor:publish not available).');
        }
        /* @codeCoverageIgnoreEnd */
    }

    private function runMigrations(): void
    {
        // During tests, avoid actually running migrations again (tables already created).
        if (app()->runningUnitTests() && (bool) config('sisp.tests.fake_migrate', true)) {
            info('Database migration completed.');

            return;
        }

        spin(fn () => $this->call('migrate'), 'Running database migrations...'); // @codeCoverageIgnore
        info('Database migration completed.'); // @codeCoverageIgnore
    }

    private function askToggle(string $key, string $question, bool $default = false): bool
    {
        if (app()->runningUnitTests()) {
            $cfg = config("sisp.tests.$key");
            if ($cfg !== null) {
                return (bool) $cfg;
            }
        }

        return confirm($question, $default);
    }

    private function detectStack(): string
    {

        $inertiaConfigPath = base_path('config/inertia.php');
        /* @codeCoverageIgnoreStart */
        if (file_exists($inertiaConfigPath)) {
            return 'inertia'; // @codeCoverageIgnore
        }
        /* @codeCoverageIgnoreEnd */

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
