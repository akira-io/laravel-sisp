<?php

namespace Akira\Sisp;

use Akira\Sisp\Actions\Fields\PaymentFields;
use Akira\Sisp\Commands\LaravelSispInstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SispServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {

        $package
            ->name('laravel-sisp')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_sisp_table')
            ->hasRoutes('web')
            ->hasCommand(LaravelSispInstallCommand::class);
    }
    
}
