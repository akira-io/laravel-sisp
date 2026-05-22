<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

it('publishes inertia/vue/assets/blade with force', function (): void {
    $targets = [
        'sisp-inertia-components' => [
            'source' => __DIR__.'/../../resources/js/react/pages/payment-response.tsx',
            'target' => resource_path('js/pages/sisp/payment-response.tsx'),
        ],
        'sisp-vue-components' => [
            'source' => __DIR__.'/../../resources/js/vue/pages/PaymentResponse.vue',
            'target' => resource_path('js/pages/sisp/PaymentResponse.vue'),
        ],
        'sisp-assets' => [
            'source' => __DIR__.'/../../resources/css/sisp.css',
            'target' => public_path('vendor/sisp/css/sisp.css'),
        ],
        'sisp-views' => [
            'source' => __DIR__.'/../../resources/views/payment-response.blade.php',
            'target' => resource_path('views/vendor/sisp/payment-response.blade.php'),
        ],
    ];

    withInstallCommandFsLock(function () use ($targets): void {
        withPublishedPathBackups(array_column($targets, 'target'), function () use ($targets): void {
            foreach ($targets as $tag => $paths) {
                File::ensureDirectoryExists(dirname($paths['target']));
                file_put_contents($paths['target'], 'stale');

                expect(Artisan::call('vendor:publish', ['--tag' => $tag]))->toBe(0)
                    ->and(file_get_contents($paths['target']))->toBe('stale')
                    ->and(Artisan::call('vendor:publish', ['--tag' => $tag, '--force' => true]))->toBe(0)
                    ->and(file_get_contents($paths['target']))->toBe(file_get_contents($paths['source']));
            }
        });
    });
});
