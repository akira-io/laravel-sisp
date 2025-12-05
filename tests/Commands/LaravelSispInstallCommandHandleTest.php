<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('drives handle() through inertia prompts via config toggles', function (): void {
    // Ensure inertia is detected via vite config containing react
    file_put_contents(base_path('vite.config.js'), "export default { plugins: ['react'] }");

    // Drive confirmations using config (test mode)
    config()->set('sisp.tests.publish_config', true);
    config()->set('sisp.tests.force_config', false);
    config()->set('sisp.tests.publish_migrations', true);
    config()->set('sisp.tests.force_migrations', false);
    // Avoid publishing to keep tests fast/stable under parallel
    config()->set('sisp.tests.publish_inertia', false);
    config()->set('sisp.tests.force_inertia', false);
    config()->set('sisp.tests.run_migrations', true);
    config()->set('sisp.tests.fake_migrate', true);
    config()->set('sisp.tests.give_star', true);

    $code = Artisan::call('sisp:install', ['--no-interaction' => true]);
    expect($code)->toBe(0);
});

it('drives handle() through blade prompts via config toggles', function (): void {
    // Ensure blade is detected (no react/inertia indicators)
    @unlink(base_path('vite.config.js'));
    @unlink(base_path('vite.config.ts'));

    config()->set('sisp.tests.publish_config', true);
    config()->set('sisp.tests.force_config', true);
    config()->set('sisp.tests.publish_migrations', true);
    config()->set('sisp.tests.force_migrations', true);
    // Avoid publishing to keep tests fast/stable under parallel
    config()->set('sisp.tests.publish_blade', false);
    config()->set('sisp.tests.force_blade', true);
    config()->set('sisp.tests.run_migrations', true);
    config()->set('sisp.tests.fake_migrate', true);
    config()->set('sisp.tests.give_star', false);

    $code = Artisan::call('sisp:install', ['--no-interaction' => true]);
    expect($code)->toBe(0);
});
