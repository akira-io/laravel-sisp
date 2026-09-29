<?php

declare(strict_types=1);

namespace Akira\Sisp\Tests;

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
        config()->set('database.connections.testing', $this->testing_connection());
        config()->set('cache.default', 'array');
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

        $app->make(Repository::class)->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app->singleton('namespace', fn (): string => 'App\\');
    }

    /**
     * @return array<string, mixed>
     */
    protected function testing_connection(): array
    {
        $driver = env('SISP_TEST_DB_DRIVER', 'sqlite');

        return match ($driver) {
            'mysql', 'mariadb' => [
                'driver' => $driver,
                'host' => env('SISP_TEST_DB_HOST', '127.0.0.1'),
                'port' => env('SISP_TEST_DB_PORT', '3306'),
                'database' => env('SISP_TEST_DB_DATABASE', 'sisp_testing'),
                'username' => env('SISP_TEST_DB_USERNAME', 'root'),
                'password' => env('SISP_TEST_DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ],
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => env('SISP_TEST_DB_HOST', '127.0.0.1'),
                'port' => env('SISP_TEST_DB_PORT', '5432'),
                'database' => env('SISP_TEST_DB_DATABASE', 'sisp_testing'),
                'username' => env('SISP_TEST_DB_USERNAME', 'postgres'),
                'password' => env('SISP_TEST_DB_PASSWORD', ''),
                'charset' => 'utf8',
                'prefix' => '',
                'search_path' => 'public',
            ],
            default => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        };
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
