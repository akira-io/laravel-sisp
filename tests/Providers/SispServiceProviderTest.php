<?php

declare(strict_types=1);

use Akira\Sisp\Sisp;
use Akira\Sisp\SispServiceProvider;
use Akira\Sisp\Configuration\LoadConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

it('registers singletons and factory guesser', function (): void {
    $provider = app()->getProvider(SispServiceProvider::class) ?? new SispServiceProvider(app());
    $provider->register();
    $provider->boot();

    expect(app()->make(LoadConfig::class))->toBeInstanceOf(LoadConfig::class)
        ->and(app()->make(Sisp::class))->toBeInstanceOf(Sisp::class);

    // Validate factory guesser mapping
    $guesser = (new class extends Factory { public function definition(): array { return []; } });
    $class = Factory::resolveFactoryName('Akira\\Sisp\\Models\\Transaction');
    expect($class)->toBe('Akira\\Sisp\\Database\\Factories\\TransactionFactory');
});

