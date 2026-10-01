<?php

declare(strict_types=1);

it('marks the node manifest as private tooling metadata', function (): void {
    $package = json_decode(file_get_contents(__DIR__.'/../package.json'), true, flags: JSON_THROW_ON_ERROR);
    $composer = json_decode(file_get_contents(__DIR__.'/../composer.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($package['private'] ?? false)->toBeTrue()
        ->and($package)->not->toHaveKey('main')
        ->and($package['license'])->toBe($composer['license'])
        ->and($package['scripts'] ?? [])->not->toHaveKey('release');
});

it('keeps the mcp server an optional dependency', function (): void {
    $manifest = json_decode((string) file_get_contents(__DIR__.'/../composer.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['require'])->not->toHaveKey('laravel/mcp')
        ->and($manifest['suggest'])->toHaveKey('laravel/mcp')
        ->and($manifest['require-dev'])->toHaveKey('laravel/mcp');
});
