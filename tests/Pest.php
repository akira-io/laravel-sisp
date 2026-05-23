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

function withPublishedPathBackups(array $paths, callable $callback): void
{
    $snapshots = [];
    $fingerprints = [];
    foreach ($paths as $path) {
        $snapshots[$path] = snapshotPublishedPath($path);
        $fingerprints[$path] = pathSnapshotFingerprint($path);
    }

    try {
        $callback();
    } finally {
        foreach ($snapshots as $path => $snapshot) {
            restorePublishedPath($path, $snapshot);
            expect(pathSnapshotFingerprint($path))->toBe($fingerprints[$path]);
        }
    }
}

function withInstallCommandProjectBackups(callable $callback): void
{
    withPublishedPathBackups([
        base_path('composer.json'),
        base_path('vite.config.js'),
        base_path('vite.config.ts'),
        base_path('config/inertia.php'),
        config_path('sisp.php'),
        database_path('migrations'),
        resource_path('views/vendor/sisp'),
        resource_path('js/pages/sisp'),
        public_path('vendor/sisp/css'),
    ], $callback);
}

function snapshotPublishedPath(string $path): array
{
    if (! file_exists($path)) {
        return ['type' => 'missing'];
    }

    if (is_file($path)) {
        return [
            'type' => 'file',
            'contents' => file_get_contents($path),
        ];
    }

    $snapshotPath = sys_get_temp_dir().'/sisp-publish-snapshot-'.bin2hex(random_bytes(8));
    Illuminate\Support\Facades\File::copyDirectory($path, $snapshotPath);

    return [
        'type' => 'directory',
        'path' => $snapshotPath,
    ];
}

function restorePublishedPath(string $path, array $snapshot): void
{
    if (is_dir($path)) {
        Illuminate\Support\Facades\File::deleteDirectory($path);
    } elseif (is_file($path)) {
        unlink($path);
    }

    if ($snapshot['type'] === 'missing') {
        return;
    }

    if ($snapshot['type'] === 'file') {
        Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($path));
        file_put_contents($path, $snapshot['contents']);

        return;
    }

    Illuminate\Support\Facades\File::copyDirectory($snapshot['path'], $path);
    Illuminate\Support\Facades\File::deleteDirectory($snapshot['path']);
}

function pathSnapshotFingerprint(string $path): array
{
    if (! file_exists($path)) {
        return ['type' => 'missing'];
    }

    if (is_file($path)) {
        return [
            'type' => 'file',
            'hash' => hash_file('sha256', $path),
        ];
    }

    $entries = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
    );

    foreach ($iterator as $entry) {
        $relativePath = mb_substr((string) $entry->getPathname(), mb_strlen($path) + 1);
        $entries[] = $entry->isDir()
            ? 'dir:'.$relativePath
            : 'file:'.$relativePath.':'.hash_file('sha256', $entry->getPathname());
    }

    sort($entries);

    return [
        'type' => 'directory',
        'entries' => $entries,
    ];
}
