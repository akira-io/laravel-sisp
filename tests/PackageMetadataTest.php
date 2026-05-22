<?php

declare(strict_types=1);

it('marks the node manifest as private tooling metadata', function (): void {
    $package = json_decode(file_get_contents(dirname(__DIR__).'/package.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($package['private'] ?? false)->toBeTrue()
        ->and($package)->not->toHaveKey('main')
        ->and($package['scripts'] ?? [])->not->toHaveKey('release');
});
