<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

it('publishes blade views, inertia components, vue components and assets', function (): void {
    $targets = [
        'sisp-views' => [
            'source' => __DIR__.'/../../resources/views/payment-form.blade.php',
            'target' => resource_path('views/vendor/sisp/payment-form.blade.php'),
            'targetDirectory' => resource_path('views/vendor/sisp'),
        ],
        'sisp-inertia-components' => [
            'source' => __DIR__.'/../../resources/js/react/pages/payment-form.tsx',
            'target' => resource_path('js/pages/sisp/payment-form.tsx'),
            'targetDirectory' => resource_path('js/pages/sisp'),
        ],
        'sisp-vue-components' => [
            'source' => __DIR__.'/../../resources/js/vue/pages/PaymentForm.vue',
            'target' => resource_path('js/pages/sisp/PaymentForm.vue'),
            'targetDirectory' => resource_path('js/pages/sisp'),
        ],
        'sisp-assets' => [
            'source' => __DIR__.'/../../resources/css/sisp.css',
            'target' => public_path('vendor/sisp/css/sisp.css'),
            'targetDirectory' => public_path('vendor/sisp/css'),
        ],
    ];

    withInstallCommandFsLock(function () use ($targets): void {
        withPublishedPathBackups(array_unique(array_column($targets, 'targetDirectory')), function () use ($targets): void {
            foreach ($targets as $tag => $paths) {
                File::delete($paths['target']);

                expect(Artisan::call('vendor:publish', ['--tag' => $tag]))->toBe(0)
                    ->and($paths['target'])->toBeFile()
                    ->and(file_get_contents($paths['target']))->toBe(file_get_contents($paths['source']));
            }
        });
    });
});
