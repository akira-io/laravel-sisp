<?php

declare(strict_types=1);

use Akira\Sisp\SispServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

it('registers blade component namespace after resolving compiler', function (): void {
    $provider = app()->getProvider(SispServiceProvider::class) ?? new SispServiceProvider(app());
    $provider->register();
    $provider->boot();

    $blade = resolve('blade.compiler');
    expect($blade)->toBeInstanceOf(BladeCompiler::class);
});

