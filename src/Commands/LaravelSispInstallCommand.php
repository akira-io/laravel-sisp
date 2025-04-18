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
        info('🌟 Starting Laravel SISP installation...');

        // Step 1: Publish config
        if (confirm('📄 Do you want to publish the configuration file?')) {
            spin(fn () => $this->callSilent('vendor:publish', [
                '--tag' => 'sisp-config',
            ]), 'Publishing configuration file...');

            info('✅ Configuration file published.');
        }

        // Step 2: Publish migrations
        if (confirm('🗄️ Do you want to publish the migration files?')) {
            spin(fn () => $this->callSilent('vendor:publish', [
                '--tag' => 'sisp-migrations',
            ]), 'Publishing migration files...');

            info('✅ Migration files published.');
        }

        // Step 3: Run migration
        if (confirm('⚙️ Do you want to run database migrations now?')) {
            spin(fn () => $this->call('migrate'), 'Running database migrations...');
            info('✅ Database migration completed.');
        }

        // Finish
        note('🎉 Laravel SISP installation completed successfully!');

        if (confirm('Would you like to support the project by giving a ⭐️ on GitHub?')) {
            note('👉 Visit: https://github.com/akira-io/laravel-sisp');
        }

        info('🙏 Thank you for choosing Laravel SISP!');

        return self::SUCCESS;
    }
}
