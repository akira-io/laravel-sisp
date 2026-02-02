<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Configuration\LoadConfig;

final readonly class PostAutCode
{
    private string $hashedCode;

    public function __construct(private LoadConfig $config)
    {
        // Optimization: Calculate hash once at construction time
        // since posAutCode is static configuration.
        $this->hashedCode = base64_encode(hash('sha512', $this->config->getPosAutCode(), true));
    }

    public function handle(): string
    {
        return $this->hashedCode;
    }
}
