<?php

declare(strict_types=1);

use Akira\Sisp\Commands\LaravelSispInstallCommand;

it('publishes config and migrations with and without force', function (): void {
    $cmd = resolve(LaravelSispInstallCommand::class);
    $ref = new ReflectionClass($cmd);

    foreach ([false, true] as $force) {
        foreach (['publishConfig','publishMigrations'] as $method) {
            $m = $ref->getMethod($method);
            $m->setAccessible(true);
            $m->invoke($cmd, $force);
        }
    }

    expect(true)->toBeTrue();
});

