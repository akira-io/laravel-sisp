<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\ValueObjects\PaymentRequest;

final readonly class RenderPaymentFormBasedOnConfigAction
{
    public function __construct(
        private RenderPaymentFormAction $render,
        private LoadConfig $config,
    ) {}

    public function handle(PaymentRequest $paymentRequest): mixed
    {
        if ($this->config->shouldUseInertia()) {
            return $this->render->renderInertia(
                $paymentRequest,
                $this->config->getPaymentFormComponent()
            );
        }

        return $this->render->renderBlade($paymentRequest);
    }
}
