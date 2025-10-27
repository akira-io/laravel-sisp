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

        $forcePublish = false;

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

        // Step 3: Publish Inertia components
        if (confirm('Do you want to publish the Inertia components?')) {
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
}
