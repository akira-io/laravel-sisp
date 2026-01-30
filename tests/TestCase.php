<?php

declare(strict_types=1);

namespace Akira\Sisp\Tests;

use Akira\Debugger\DebuggerServiceProvider;
use Akira\Sisp\SispServiceProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Akira\\Sisp\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    final public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        config()->set('sisp.url', 'https://test.sisp.example.com');
        config()->set('sisp.posID', 'TEST_POS_001');
        config()->set('sisp.posAutCode', 'TEST_POS_AUT_CODE');
        config()->set('sisp.merchantId', 'TEST_MERCHANT_ID');
        config()->set('sisp.currency', '132');
        config()->set('sisp.language_messages', 'EN');
        config()->set('sisp.fingerprint_version', '1');
        config()->set('sisp.is_3dsec', '0');
        config()->set('sisp.transaction_code', '1');
        config()->set('sisp.url_merchant_response', 'https://localhost/sisp/callback');

        // App key for encryption
        $app->make(Repository::class)->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        // Ensure a base layout exists for package Blade views extending 'layouts.app'
        $paths = $app->make(Repository::class)->get('view.paths', []);
        $testViews = __DIR__.'/resources/views';
        if (! in_array($testViews, $paths, true)) {
            $paths[] = $testViews;
            $app->make(Repository::class)->set('view.paths', $paths);
        }
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            SispServiceProvider::class,
        ];
    }
}
