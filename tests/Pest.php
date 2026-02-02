<?php

declare(strict_types=1);

use Akira\Sisp\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function withInstallCommandFsLock(callable $callback): void
{
    $lockPath = sys_get_temp_dir().'/sisp-install-tests.lock';
    $handle = fopen($lockPath, 'c');
    if ($handle === false) {
        $callback();

        return;
    }

    try {
        flock($handle, LOCK_EX);
        $callback();
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function withFileBackups(array $paths, callable $callback): void
{
    $snapshots = [];
    foreach ($paths as $path) {
        $snapshots[$path] = file_exists($path) ? file_get_contents($path) : null;
    }

    try {
        $callback();
    } finally {
        foreach ($snapshots as $path => $contents) {
            if ($contents === null) {
                @unlink($path);
            } else {
                file_put_contents($path, $contents);
            }
        }
    }
}
