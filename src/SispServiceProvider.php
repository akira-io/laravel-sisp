<?php

declare(strict_types=1);

namespace Akira\Sisp;

use Akira\Sisp\Commands\DoctorCommand;
use Akira\Sisp\Commands\ExpirePendingTransactionsCommand;
use Akira\Sisp\Commands\LaravelSispInstallCommand;
use Akira\Sisp\Commands\ReconcilePendingTransactionsCommand;
use Akira\Sisp\Commands\RegenerateMissingInvoicePdfsCommand;
use Akira\Sisp\Commands\TransactionStatusCommand;
use Akira\Sisp\Contracts\SispDriver;
use Akira\Sisp\Drivers\SispManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
                'update_laravel_sisp_transactions_add_created_at_index',
                'create_sisp_transaction_logs_table',
                'create_sisp_transaction_attempts_table',
                'create_sisp_payment_intents_table',
                'add_fiscal_fields_to_sisp_tables',
                'update_laravel_sisp_transactions_add_callback_error_fields',
                'create_sisp_refunds_table',
            ])
            ->hasTranslations()
            ->hasRoutes('web')
            ->hasCommands([
                LaravelSispInstallCommand::class,
                RegenerateMissingInvoicePdfsCommand::class,
                ReconcilePendingTransactionsCommand::class,
                ExpirePendingTransactionsCommand::class,
                TransactionStatusCommand::class,
                DoctorCommand::class,
            ]);
    }

    public function register(): void
    {
        parent::register();

        $this->app->resolveEnvironmentUsing(fn (array $environments): bool => (bool) $this->app->environment($environments));

        $this->app->bind(SispDriver::class, fn (Application $app): SispDriver => $app->make(SispManager::class)->driver());
    }

    public function boot(): self
    {
        $this->registerComponents();
        $this->registerFactories();
        $this->registerCallbackRateLimiter();

        return parent::boot();
    }

    private function registerCallbackRateLimiter(): void
    {
        RateLimiter::for('sisp-callback', function (Request $request): Limit {
            if (! $request->boolean('UserCancelled')) {
                return Limit::none();
            }

            return Limit::perMinute(10)->by((string) $request->input('merchantRef', $request->ip()));
        });
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
