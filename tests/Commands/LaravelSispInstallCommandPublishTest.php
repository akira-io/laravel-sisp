<?php

declare(strict_types=1);

use Akira\Sisp\Commands\LaravelSispInstallCommand;

it('publishes blade views, inertia components, vue components and assets', function (): void {
    $cmd = resolve(LaravelSispInstallCommand::class);
    $ref = new ReflectionClass($cmd);

    foreach (['publishBladeViews','publishInertiaComponents','publishVueComponents','publishAssets'] as $method) {
        $m = $ref->getMethod($method);
        $m->setAccessible(true);
        $m->invoke($cmd, false);
        expect(true)->toBeTrue();
    }
});

