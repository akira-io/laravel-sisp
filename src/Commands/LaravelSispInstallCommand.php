<?php

namespace Akira\Sisp\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;

class LaravelSispInstallCommand extends Command
{
    public $signature = 'laravel-sisp:install';

    public $description = 'Install and configure Laravel SISP package.';

    public function handle(): int
    {
        \Laravel\Prompts\info('Starting Laravel SISP installation...');

        if (confirm('Do you want to publish the configuration file?')) {
            $this->call('vendor:publish', [
                '--tag' => 'sisp-config',
            ]);
            \Laravel\Prompts\info('Configuration file published.');
        }

        if (confirm('Do you want to publish the migration files?')) {
            $this->call('vendor:publish', [
                '--tag' => 'sisp-migrations',
            ]);
            \Laravel\Prompts\info('Migration files published.');
        }

        if (confirm('Do you want to migrate the database now?')) {
            $this->call('migrate');
            \Laravel\Prompts\info('Database migration completed.');
        }

        $this->line('');
        \Laravel\Prompts\info('Laravel SISP installation completed successfully!');

        if (confirm('Would you like to give a ⭐️ on GitHub to support the project?')) {
            $this->openGitHubRepo();
        }

        \Laravel\Prompts\info('Thank you for choosing Laravel SISP!');

        return Command::SUCCESS;
    }

    /**
     * Abre o repositório no navegador.
     */
    protected function openGitHubRepo(): void
    {
        $repoUrl = 'https://github.com/akira-io/laravel-sisp';

        if (PHP_OS_FAMILY === 'Windows') {
            exec("start $repoUrl");
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            exec("open $repoUrl"); // macOS
        } else {
            exec("xdg-open $repoUrl"); // Linux
        }

        info('GitHub repository has been opened in your browser. Thank you for your support!');
    }
}
