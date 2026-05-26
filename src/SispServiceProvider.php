<?php

declare(strict_types=1);

namespace Akira\Sisp;

use Akira\Sisp\Actions\BuildRequestPayloadAction;
use Akira\Sisp\Actions\BuildSandboxPayloadAction;
use Akira\Sisp\Actions\CreateTransactionAction;
use Akira\Sisp\Actions\HandleCallbackAction;
use Akira\Sisp\Actions\ValidatePaymentResponseFingerprintAction;
use Akira\Sisp\Commands\DoctorCommand;
use Akira\Sisp\Commands\LaravelSispInstallCommand;
use Akira\Sisp\Commands\ReconcilePendingTransactionsCommand;
use Akira\Sisp\Commands\RegenerateMissingInvoicePdfsCommand;
use Akira\Sisp\Commands\TransactionStatusCommand;
use Akira\Sisp\Configuration\EnvSispCredentialsResolver;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Contracts\SispCredentialsResolver;
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

    public function register(): void
    {
        parent::register();

        $this->app->singleton(LoadConfig::class);

        $this->app->singleton(
            SispCredentialsResolver::class,
            EnvSispCredentialsResolver::class
        );

        $this->app->singleton(Sisp::class, fn (Application $app): Sisp => new Sisp(
            buildRequestPayload: $app->make(BuildRequestPayloadAction::class),
            buildSandboxPayload: $app->make(BuildSandboxPayloadAction::class),
            validateFingerprint: $app->make(ValidatePaymentResponseFingerprintAction::class),
            createTransaction: $app->make(CreateTransactionAction::class),
            handleCallback: $app->make(HandleCallbackAction::class),
            loadConfig: $app->make(LoadConfig::class),
        ));
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
