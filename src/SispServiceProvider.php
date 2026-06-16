<?php

declare(strict_types=1);

namespace Akira\Sisp;

use Akira\Sisp\Commands\DoctorCommand;
use Akira\Sisp\Commands\LaravelSispInstallCommand;
use Akira\Sisp\Commands\ReconcilePendingTransactionsCommand;
use Akira\Sisp\Commands\RegenerateMissingInvoicePdfsCommand;
use Akira\Sisp\Commands\TransactionStatusCommand;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Contracts\SispDriver;
use Akira\Sisp\Drivers\SispManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\View\Compilers\BladeCompiler;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class SispServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-sisp')
            ->hasConfigFile()
            ->hasMigrations([
                'create_laravel_sisp_table',
                'update_laravel_sisp_transactions_add_amount_cents',
                'create_sisp_transaction_logs_table',
                'create_sisp_transaction_attempts_table',
                'create_sisp_payment_intents_table',
            ])
            ->hasTranslations()
            ->hasRoutes('web')
            ->hasCommands([
                LaravelSispInstallCommand::class,
                RegenerateMissingInvoicePdfsCommand::class,
                ReconcilePendingTransactionsCommand::class,
                TransactionStatusCommand::class,
                DoctorCommand::class,
            ]);
    }

    /**
     * Container bindings are declared with native container
     * attributes: #[Bind] on the contracts and #[Singleton] on
     * LoadConfig, SispManager, and Sisp. Only the driver contract needs
     * an explicit closure since it is resolved through the manager.
     */
    public function register(): void
    {
        parent::register();

        // #[Bind] container attributes are only consulted when an environment
        // resolver is present. Full applications register one during bootstrap,
        // but lighter harnesses such as Testbench do not, so mirror the
        // framework's default resolver here.
        $this->app->resolveEnvironmentUsing(fn (array $environments): bool => (bool) $this->app->environment($environments));

        $this->app->bind(SispDriver::class, fn (Application $app): SispDriver => $app->make(SispManager::class)->driver());
    }

    public function boot(): self
    {
        $this->registerComponents();
        $this->registerFactories();

        return parent::boot();
    }

    private function registerFactories(): void
    {
        Factory::guessFactoryNamesUsing(function (string $modelName): string {
            if (str_starts_with($modelName, 'Akira\\Sisp\\')) {
                return 'Akira\\Sisp\\Database\\Factories\\'.class_basename($modelName).'Factory';
            }

            return 'Database\\Factories\\'.class_basename($modelName).'Factory';
        });
    }

    private function registerComponents(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sisp');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/sisp'),
        ], 'sisp-views');

        $this->publishes([
            __DIR__.'/../resources/js/react/pages' => resource_path('js/pages/sisp'),
        ], 'sisp-inertia-components');

        $this->publishes([
            __DIR__.'/../resources/js/vue/pages' => resource_path('js/pages/sisp'),
        ], 'sisp-vue-components');

        $this->publishes([
            __DIR__.'/../resources/css' => public_path('vendor/sisp/css'),
        ], 'sisp-assets');

        $this->callAfterResolving('blade.compiler', function (BladeCompiler $blade): void {
            $blade->anonymousComponentNamespace('sisp', __DIR__.'/../resources/views/components');
        });
    }
}
