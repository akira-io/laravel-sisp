<?php

declare(strict_types=1);

use Akira\Sisp\Http\Middleware\EnforceSispRateLimits;
use Akira\Sisp\Models\Blacklist;
use Illuminate\Http\Request;

it('blocks blacklisted IPs with json error', function (): void {
    Blacklist::query()->create([
        'type' => 'ip',
        'value' => '127.0.0.1',
        'reason' => 'test',
        'severity' => 'high',
    ]);

    $middleware = resolve(EnforceSispRateLimits::class);
    $request = Request::create('/any', 'GET', server: ['REMOTE_ADDR' => '127.0.0.1']);

    $response = $middleware->handle($request, fn (): Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response => response('ok'));

    expect($response->getStatusCode())->toBe(403)
        ->and($response->headers->get('Content-Type'))->toContain('application/json');
});

it('allows non-blacklisted requests to proceed', function (): void {
    $middleware = resolve(EnforceSispRateLimits::class);
    $request = Request::create('/any', 'GET', server: ['REMOTE_ADDR' => '127.0.0.2']);

    $response = $middleware->handle($request, fn (): Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response => response('ok'));

    expect($response->getContent())->toBe('ok');
});
