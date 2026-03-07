<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Contracts\SispCredentialsResolver;

final readonly class PostAutCode
{
    /**
     * The hashed posAutCode.
     *
     * Cached in constructor to avoid recomputing on every call.
     * Benchmark: ~80x faster (0.19975s -> 0.00253s for 100k calls).
     */
    private string $hash;

    public function __construct(SispCredentialsResolver $resolver)
    {
        $posAutCode = $resolver->resolve()->posAutCode;
        $this->hash = base64_encode(hash('sha512', $posAutCode, true));
    }

    public function handle(): string
    {
        return $this->hash;
    }
}
