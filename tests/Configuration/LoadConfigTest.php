<?php

declare(strict_types=1);

use Akira\Sisp\Configuration\LoadConfig;

beforeEach(function (): void {
    $this->config = resolve(LoadConfig::class);
});

it('returns default true for allow retry', function (): void {
    expect($this->config->isRetryAllowed())->toBeTrue();
});

it('respects allow retry configuration', function (): void {
    config(['sisp.allow_retry' => false]);

    expect($this->config->isRetryAllowed())->toBeFalse();
});

it('returns true when allow retry is explicitly enabled', function (): void {
    config(['sisp.allow_retry' => true]);

    expect($this->config->isRetryAllowed())->toBeTrue();
});

it('allows toggling retry via config', function (): void {
    config(['sisp.allow_retry' => true]);
    expect($this->config->isRetryAllowed())->toBeTrue();

    config(['sisp.allow_retry' => false]);
    expect($this->config->isRetryAllowed())->toBeFalse();

    config(['sisp.allow_retry' => true]);
    expect($this->config->isRetryAllowed())->toBeTrue();
});

it('returns identifier generation settings', function (): void {
    expect($this->config->getIdentifierGenerationMaxAttempts())->toBe(5)
        ->and($this->config->getIdentifierGenerationCollisionRetrySleepMicroseconds())->toBe(1000000);

    config(['sisp.identifier_generation.max_attempts' => 3]);
    config(['sisp.identifier_generation.collision_retry_sleep_microseconds' => 250000]);

    expect($this->config->getIdentifierGenerationMaxAttempts())->toBe(3)
        ->and($this->config->getIdentifierGenerationCollisionRetrySleepMicroseconds())->toBe(250000);
});

it('normalizes invalid identifier generation settings', function (): void {
    config(['sisp.identifier_generation.max_attempts' => 0]);
    config(['sisp.identifier_generation.collision_retry_sleep_microseconds' => -1]);

    expect($this->config->getIdentifierGenerationMaxAttempts())->toBe(1)
        ->and($this->config->getIdentifierGenerationCollisionRetrySleepMicroseconds())->toBe(0);
});
