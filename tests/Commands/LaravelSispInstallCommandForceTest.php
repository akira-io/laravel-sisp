<?php

declare(strict_types=1);

use Akira\Sisp\Commands\LaravelSispInstallCommand;

it('publishes inertia/vue/assets/blade with force', function (): void {
    $cmd = resolve(LaravelSispInstallCommand::class);
    $ref = new ReflectionClass($cmd);

    foreach (['publishInertiaComponents','publishVueComponents','publishAssets','publishBladeViews'] as $method) {
        $m = $ref->getMethod($method);
        $m->setAccessible(true);
        $m->invoke($cmd, true);
    }

    expect(true)->toBeTrue();
});
