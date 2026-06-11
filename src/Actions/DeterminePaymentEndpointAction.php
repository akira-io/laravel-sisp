<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Drivers\SispManager;

final readonly class DeterminePaymentEndpointAction
{
    public function __construct(private SispManager $manager) {}

    public function handle(): string
    {
        return $this->manager->driver()->paymentEndpoint();
    }
}
