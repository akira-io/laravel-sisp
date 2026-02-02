<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Contracts\SispCredentialsResolver;

final readonly class DeterminePaymentEndpointAction
{
    public function __construct(private SispCredentialsResolver $resolver) {}

    public function handle(): string
    {
        $credentials = $this->resolver->resolve();

        if ($credentials->sandbox) {
            return route('sisp.sandbox');
        }

        return $credentials->url;
    }
}
