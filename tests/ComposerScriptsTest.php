<?php

declare(strict_types=1);

it('guards the autoload hook from dev-only testbench dependency', function (): void {
    $composer = json_decode(
        file_get_contents(__DIR__.'/../composer.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($composer['scripts']['post-autoload-dump'])->toBe('@php scripts/prepare-testbench.php')
        ->and($composer['scripts']['prepare'])->toBe('@php vendor/bin/testbench package:discover --ansi');
});

it('skips package discovery when testbench is unavailable', function (): void {
    $script = escapeshellarg(__DIR__.'/../scripts/prepare-testbench.php');
    $missingBinary = escapeshellarg(sys_get_temp_dir().'/missing-testbench-binary');

    exec("SISP_TESTBENCH_BINARY={$missingBinary} ".escapeshellarg(PHP_BINARY)." {$script}", result_code: $exitCode);

    expect($exitCode)->toBe(0);
});

it('skips package discovery when composer runs without dev dependencies', function (): void {
    $script = escapeshellarg(__DIR__.'/../scripts/prepare-testbench.php');

    exec('COMPOSER_DEV_MODE=0 '.escapeshellarg(PHP_BINARY)." {$script}", result_code: $exitCode);

    expect($exitCode)->toBe(0);
});
