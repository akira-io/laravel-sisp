<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Contracts\SispCredentialsResolver;

final readonly class PostAutCode
{
    public function __construct(private SispCredentialsResolver $resolver) {}

    public function handle(): string
    {
        $posAutCode = $this->resolver->resolve()->posAutCode;

        return base64_encode(hash('sha512', $posAutCode, true));
    }
}
