<?php

declare(strict_types=1);

namespace Akira\Sisp;

use Akira\Sisp\Actions\BuildRequestPayloadAction;
use Akira\Sisp\Actions\BuildSandboxPayloadAction;
use Akira\Sisp\Actions\CreateTransactionAction;
use Akira\Sisp\Actions\HandleCallbackAction;
use Akira\Sisp\Actions\ValidatePaymentResponseFingerprintAction;
use Akira\Sisp\Commands\LaravelSispInstallCommand;
use Akira\Sisp\Configuration\LoadConfig;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\View\Compilers\BladeCompiler;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class SispServiceProvider extends PackageServiceProvider
{
    /**
     * Register the package services.
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-sisp')
            ->hasConfigFile()
            ->hasMigration('create_laravel_sisp_table')
            ->hasTranslations()
            ->hasRoutes('web')
            ->hasCommand(LaravelSispInstallCommand::class);
    }

    /**
     * Register the package's services in the container.
     */
    public function register(): void
    {
        parent::register();

        $this->app->singleton(LoadConfig::class);
        $this->app->singleton(Sisp::class, fn($app): \Akira\Sisp\Sisp => new Sisp(
            buildRequestPayload: $app->make(BuildRequestPayloadAction::class),
            buildSandboxPayload: $app->make(BuildSandboxPayloadAction::class),
            validateFingerprint: $app->make(ValidatePaymentResponseFingerprintAction::class),
            createTransaction: $app->make(CreateTransactionAction::class),
            handleCallback: $app->make(HandleCallbackAction::class),
            loadConfig: $app->make(LoadConfig::class),
        ));
    }

    /**
     * Perform post-registration booting of services.
     */
    public function boot(): self
    {
        $this->registerComponents();
        $this->registerFactories();

        return parent::boot();
    }

    /**
     * Register package factories.
     */
    private function registerFactories(): void
    {
        Factory::guessFactoryNamesUsing(function (string $modelName): string {
            if (str_starts_with($modelName, 'Akira\\Sisp\\')) {
                return 'Akira\\Sisp\\Database\\Factories\\'.class_basename($modelName).'Factory';
            }

            return 'Database\\Factories\\'.class_basename($modelName).'Factory';
        });
    }

    /**
     * Register the package's Blade components and views.
     */
    private function registerComponents(): void
    {
        // Load Blade views from package
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sisp');

        // Allow users to publish Blade views for customization
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/sisp'),
        ], 'sisp-views');

        // Publish React components for customization
        $this->publishes([
            __DIR__.'/../resources/js/react/pages' => resource_path('js/pages/sisp'),
        ], 'sisp-inertia-components');

        // Publish Vue components for customization
        $this->publishes([
            __DIR__.'/../resources/js/vue/pages' => resource_path('js/pages/sisp'),
        ], 'sisp-vue-components');

        // Publish CSS assets
        $this->publishes([
            __DIR__.'/../resources/css' => public_path('vendor/sisp/css'),
        ], 'sisp-assets');

        // Register Blade anonymous component namespace
        $this->callAfterResolving('blade.compiler', function (BladeCompiler $blade): void {
            $blade->anonymousComponentNamespace('akira-sisp', __DIR__.'/../resources/views/components');
        });
    }
}
