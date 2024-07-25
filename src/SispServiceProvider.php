<?php

namespace Akira\Sisp;

use Akira\Sisp\Commands\SispCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SispServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-sisp')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_sisp_table')
            ->hasCommand(SispCommand::class);
    }
}
