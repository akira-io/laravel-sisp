<?php

declare(strict_types=1);

namespace Akira\Sisp;

use Akira\Sisp\Commands\LaravelSispInstallCommand;
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
            ->hasViews()
            ->hasMigration('create_laravel_sisp_table')
            ->hasTranslations()
            ->hasRoutes('web')
//            ->hasAssets()
            ->hasCommand(LaravelSispInstallCommand::class);
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
     * Register the package's Blade components.
     */
    private function registerComponents(): void
    {
        $this->publishes([
            __DIR__.'/../resources/css' => public_path('vendor/sisp/css'),
        ], 'sisp-assets');

        $this->callAfterResolving('blade.compiler', function (BladeCompiler $blade): void {
            $blade->anonymousComponentNamespace('akira-sisp', __DIR__.'/../resources/views/components');
        });
    }
}
