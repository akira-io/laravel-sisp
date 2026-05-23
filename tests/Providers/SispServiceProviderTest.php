<?php

declare(strict_types=1);

use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Sisp;
use Akira\Sisp\SispServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

it('registers singletons and factory guesser', function (): void {
    $factoryNameBeforeTest = Factory::resolveFactoryName('App\\Models\\User');

    withFactoryNameResolverSnapshot(function (): void {
        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Original\\Factories\\'.class_basename($modelName).'Factory'
        );

        withFactoryNameResolverSnapshot(function (): void {
            $provider = app()->getProvider(SispServiceProvider::class) ?? new SispServiceProvider(app());
            $provider->register();
            $provider->boot();

            expect(app()->make(LoadConfig::class))->toBeInstanceOf(LoadConfig::class)
                ->and(app()->make(Sisp::class))->toBeInstanceOf(Sisp::class)
                ->and(Factory::resolveFactoryName(Akira\Sisp\Models\Transaction::class))->toBe(Akira\Sisp\Database\Factories\TransactionFactory::class)
                ->and(Factory::resolveFactoryName('App\\Models\\User'))->toBe('Database\\Factories\\UserFactory');
        });

        expect(Factory::resolveFactoryName('App\\Models\\User'))->toBe('Original\\Factories\\UserFactory');
    });

    expect(Factory::resolveFactoryName('App\\Models\\User'))->toBe($factoryNameBeforeTest);
});

function withFactoryNameResolverSnapshot(callable $callback): void
{
    $property = new ReflectionProperty(Factory::class, 'factoryNameResolver');
    $previousResolver = $property->getValue();

    try {
        $callback();
    } finally {
        $property->setValue(null, $previousResolver);
    }
}
