<?php

declare(strict_types=1);

namespace Akira\Sisp\Drivers;

use Akira\Sisp\Contracts\SispCredentialsResolver;
use Akira\Sisp\Contracts\SispDriver;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Manager;

#[Singleton]
final class SispManager extends Manager
{
    public function getDefaultDriver(): string
    {
        $configured = $this->config->get('sisp.driver');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return $this->container->make(SispCredentialsResolver::class)->resolve()->sandbox
            ? 'sandbox'
            : 'production';
    }

    protected function createProductionDriver(): SispDriver
    {
        return $this->container->make(ProductionDriver::class);
    }

    protected function createSandboxDriver(): SispDriver
    {
        return $this->container->make(SandboxDriver::class);
    }
}
