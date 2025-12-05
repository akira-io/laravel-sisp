<?php

declare(strict_types=1);

use Akira\Sisp\Commands\LaravelSispInstallCommand;
use Illuminate\Support\Facades\Artisan;

it('detects inertia stack when composer requires inertia', function (): void {
    // Create a composer.json that includes inertia
    $composerPath = base_path('composer.json');
    file_put_contents($composerPath, json_encode([
        'require' => [
            'inertiajs/inertia-laravel' => '^2.0',
        ],
    ], JSON_PRETTY_PRINT));

    $cmd = resolve(LaravelSispInstallCommand::class);
    $ref = new ReflectionClass($cmd);
    $m = $ref->getMethod('detectStack');
    $m->setAccessible(true);

    $stack = $m->invoke($cmd);
    expect($stack)->toBe('inertia');
});

it('falls back to blade when no inertia indicators are present', function (): void {
    // Ensure vite configs do not contain react
    @unlink(base_path('vite.config.ts'));
    @unlink(base_path('vite.config.js'));
    @unlink(base_path('config/inertia.php'));
    // Overwrite composer.json without inertia requirement
    file_put_contents(base_path('composer.json'), json_encode([
        'require' => [
            'php' => '^8.4'
        ],
    ], JSON_PRETTY_PRINT));

    $cmd = resolve(LaravelSispInstallCommand::class);
    $ref = new ReflectionClass($cmd);
    $m = $ref->getMethod('detectStack');
    $m->setAccessible(true);

    $stack = $m->invoke($cmd);
    expect($stack)->toBe('blade');
});

it('runs install command non-interactively', function (): void {
    // Should not hang; with no-interaction the confirmations default to false
    $code = Artisan::call('sisp:install', ['--no-interaction' => true]);
    expect($code)->toBe(0);
});

it('detects inertia stack via vite config containing react', function (): void {
    file_put_contents(base_path('vite.config.js'), "export default { plugins: ['react'] }");
    $cmd = resolve(LaravelSispInstallCommand::class);
    $ref = new ReflectionClass($cmd);
    $m = $ref->getMethod('detectStack');
    $m->setAccessible(true);
    $stack = $m->invoke($cmd);
    expect($stack)->toBe('inertia');
});

it('detects inertia stack via inertia.php config file', function (): void {
    @mkdir(base_path('config'), 0777, true);
    file_put_contents(base_path('config/inertia.php'), "<?php return []; ");
    $cmd = resolve(LaravelSispInstallCommand::class);
    $ref = new ReflectionClass($cmd);
    $m = $ref->getMethod('detectStack');
    $m->setAccessible(true);
    $stack = $m->invoke($cmd);
    expect($stack)->toBe('inertia');
});

it('detects inertia stack via vite.config.ts containing react', function (): void {
    @unlink(base_path('vite.config.js'));
    file_put_contents(base_path('vite.config.ts'), "export default { plugins: ['react'] }");
    $cmd = resolve(LaravelSispInstallCommand::class);
    $ref = new ReflectionClass($cmd);
    $m = $ref->getMethod('detectStack');
    $m->setAccessible(true);
    $stack = $m->invoke($cmd);
    expect($stack)->toBe('inertia');
});
