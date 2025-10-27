<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Configuration\LoadConfig;

final readonly class PostAutCode
{
    public function __construct(private LoadConfig $config) {}

    /**
     * Encode the post authorization code and return it.
     */
    public function handle(): string
    {
        $posAutCode = $this->config->getPosAutCode();

        return base64_encode(hash('sha512', $posAutCode, true));
    }
}
