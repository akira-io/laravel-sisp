<?php

declare(strict_types=1);

use Akira\Sisp\Actions\PostAutCode;
use Akira\Sisp\Contracts\SispCredentialsResolver;
use Akira\Sisp\ValueObjects\SispCredentials;

it('generates base64 sha512 of posAutCode', function (): void {
    $action = resolve(PostAutCode::class);
    $expected = base64_encode(hash('sha512', (string) config('sisp.posAutCode'), true));

    expect($action->handle())->toBe($expected);
});

it('resolves credentials once and reuses the hashed value', function (): void {
    $resolver = new class implements SispCredentialsResolver
    {
        public int $calls = 0;

        public function resolve(): SispCredentials
        {
            $this->calls++;

            return SispCredentials::from(['posAutCode' => 'SECRET']);
        }
    };

    $action = new PostAutCode($resolver);
    $expected = base64_encode(hash('sha512', 'SECRET', true));

    expect($action->handle())->toBe($expected)
        ->and($action->handle())->toBe($expected)
        ->and($action->handle())->toBe($expected)
        ->and($resolver->calls)->toBe(1);
});
