<?php

declare(strict_types=1);

use Akira\Sisp\Commands\LaravelSispInstallCommand;
use Illuminate\Support\Facades\Artisan;

it('detects inertia stack when composer requires inertia', function (): void {
    withInstallCommandFsLock(function (): void {
        $composerPath = base_path('composer.json');
        withFileBackups([$composerPath], function () use ($composerPath): void {
            // Create a composer.json that includes inertia
            file_put_contents($composerPath, json_encode([
                'require' => [
                    'inertiajs/inertia-laravel' => '^2.0',
                ],
            ], JSON_PRETTY_PRINT));

            $cmd = resolve(LaravelSispInstallCommand::class);
            $ref = new ReflectionClass($cmd);
            $m = $ref->getMethod('detectStack');

            $stack = $m->invoke($cmd);
            expect($stack)->toBe('inertia');
        });
    });
});

it('falls back to blade when no inertia indicators are present', function (): void {
    withInstallCommandFsLock(function (): void {
        $viteTs = base_path('vite.config.ts');
        $viteJs = base_path('vite.config.js');
        $inertiaConfig = base_path('config/inertia.php');
        $composerPath = base_path('composer.json');

        withFileBackups([$viteTs, $viteJs, $inertiaConfig, $composerPath], function () use (
            $viteTs,
            $viteJs,
            $inertiaConfig,
            $composerPath
        ): void {
            // Ensure vite configs do not contain react
            @unlink($viteTs);
            @unlink($viteJs);
            @unlink($inertiaConfig);
            // Overwrite composer.json without inertia requirement
            file_put_contents($composerPath, json_encode([
                'require' => [
                    'php' => '^8.4',
                ],
            ], JSON_PRETTY_PRINT));

            $cmd = resolve(LaravelSispInstallCommand::class);
            $ref = new ReflectionClass($cmd);
            $m = $ref->getMethod('detectStack');

            $stack = $m->invoke($cmd);
            expect($stack)->toBe('blade');
        });
    });
});

it('runs install command non-interactively', function (): void {
    // Should not hang; with no-interaction the confirmations default to false
    $code = Artisan::call('sisp:install', ['--no-interaction' => true]);
    expect($code)->toBe(0);
});

it('detects inertia stack via vite config containing react', function (): void {
    withInstallCommandFsLock(function (): void {
        $viteJs = base_path('vite.config.js');
        $viteTs = base_path('vite.config.ts');
        withFileBackups([$viteJs, $viteTs], function () use ($viteJs, $viteTs): void {
            @unlink($viteTs);
            file_put_contents($viteJs, "export default { plugins: ['react'] }");
            $cmd = resolve(LaravelSispInstallCommand::class);
            $ref = new ReflectionClass($cmd);
            $m = $ref->getMethod('detectStack');
            $stack = $m->invoke($cmd);
            expect($stack)->toBe('inertia');
        });
    });
});

it('detects inertia stack via vite.config.ts containing react', function (): void {
    withInstallCommandFsLock(function (): void {
        $viteJs = base_path('vite.config.js');
        $viteTs = base_path('vite.config.ts');
        withFileBackups([$viteJs, $viteTs], function () use ($viteJs, $viteTs): void {
            @unlink($viteJs);
            file_put_contents($viteTs, "export default { plugins: ['react'] }");
            $cmd = resolve(LaravelSispInstallCommand::class);
            $ref = new ReflectionClass($cmd);
            $m = $ref->getMethod('detectStack');
            $stack = $m->invoke($cmd);
            expect($stack)->toBe('inertia');
        });
    });
});
