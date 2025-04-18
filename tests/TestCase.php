<?php

declare(strict_types=1);

namespace Akira\Sisp\Tests;

use Akira\Sisp\SispServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Akira\\Sisp\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    final public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_laravel-sisp_table.php.stub';
        $migration->up();
        */
    }

    protected function getPackageProviders($app)
    {
        return [
            SispServiceProvider::class,
        ];
    }
}
