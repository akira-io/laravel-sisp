<?php

declare(strict_types=1);

$binary = getenv('SISP_TESTBENCH_BINARY') ?: __DIR__.'/../vendor/bin/testbench';

if (getenv('COMPOSER_DEV_MODE') === '0') {
    exit(0);
}

if (! file_exists($binary)) {
    exit(0);
}

passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg($binary).' package:discover --ansi', $exitCode);

exit($exitCode);
