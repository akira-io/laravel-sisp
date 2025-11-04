<?php

declare(strict_types=1);

namespace Akira\Sisp;

use Akira\Sisp\Actions\BuildRequestPayloadAction;
use Akira\Sisp\Actions\BuildSandboxPayloadAction;
use Akira\Sisp\Actions\CreateTransactionAction;
use Akira\Sisp\Actions\HandleCallbackAction;
use Akira\Sisp\Actions\ValidateFingerprintAction;
use Akira\Sisp\Commands\LaravelSispInstallCommand;
use Akira\Sisp\Configuration\LoadConfig;
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
        $this->app->singleton(Sisp::class, function ($app) {
            return new Sisp(
                buildRequestPayload: $app->make(BuildRequestPayloadAction::class),
                buildSandboxPayload: $app->make(BuildSandboxPayloadAction::class),
                validateFingerprint: $app->make(ValidateFingerprintAction::class),
                createTransaction: $app->make(CreateTransactionAction::class),
                handleCallback: $app->make(HandleCallbackAction::class),
                loadConfig: $app->make(LoadConfig::class),
            );
        });
    }

    /**
     * Perform post-registration booting of services.
     */
    public function boot(): self
    {
        $this->registerComponents();

        return parent::boot();
    }

    /**
     * Register the package's Blade components and views.
     */
    private function registerComponents(): void
    {

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sisp');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/sisp'),
        ], 'sisp-views');

        $this->publishes([
            __DIR__.'/../resources/css' => public_path('vendor/sisp/css'),
        ], 'sisp-assets');

        $this->callAfterResolving('blade.compiler', function (BladeCompiler $blade): void {
            $blade->anonymousComponentNamespace('akira-sisp', __DIR__.'/../resources/views/components');
        });
    }
}
