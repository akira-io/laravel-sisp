<?php

declare(strict_types=1);

use Akira\Sisp\Actions\PostAutCode;

it('generates base64 sha512 of posAutCode', function (): void {
    $action = resolve(PostAutCode::class);
    $expected = base64_encode(hash('sha512', (string) config('sisp.posAutCode'), true));

    expect($action->handle())->toBe($expected);
});
