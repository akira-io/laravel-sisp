<?php

declare(strict_types=1);

use Akira\Sisp\Configuration\LoadConfig;

beforeEach(function () {
    $this->config = app(LoadConfig::class);
});

it('returns default true for allow retry', function () {
    expect($this->config->isRetryAllowed())->toBeTrue();
});

it('respects allow retry configuration', function () {
    config(['sisp.allow_retry' => false]);

    expect($this->config->isRetryAllowed())->toBeFalse();
});

it('returns true when allow retry is explicitly enabled', function () {
    config(['sisp.allow_retry' => true]);

    expect($this->config->isRetryAllowed())->toBeTrue();
});

it('allows toggling retry via config', function () {
    config(['sisp.allow_retry' => true]);
    expect($this->config->isRetryAllowed())->toBeTrue();

    config(['sisp.allow_retry' => false]);
    expect($this->config->isRetryAllowed())->toBeFalse();

    config(['sisp.allow_retry' => true]);
    expect($this->config->isRetryAllowed())->toBeTrue();
});