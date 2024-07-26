<?php

namespace Akira\Sisp;

use Akira\Sisp\Commands\SispCommand;
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
            ->hasCommand(SispCommand::class);
    }

    //    public function register(): void
    //    {
    //        parent::register();
    //
    //        $this->app->bind(PaymentFields::class, function ($app, $parameters) {
    //            return new PaymentFields($parameters['amount']);
    //        });
    //    }
}
