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
        config()->set('sisp.posAutCode', 'TEST_POS_AUT_CODE');
        config()->set('sisp.merchantReference', 'TEST_MERCHANT_REF');
        config()->set('sisp.merchantSession', 'TEST_MERCHANT_SESSION');
        config()->set('sisp.posId', 'TEST_POS_001');
        config()->set('sisp.currency', 'AOA');
        config()->set('sisp.defaultTransactionCode', 'PURCHASE');

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
