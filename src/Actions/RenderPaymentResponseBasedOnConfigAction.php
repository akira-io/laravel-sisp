<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Models\Transaction;

final readonly class RenderPaymentResponseBasedOnConfigAction
{
    public function __construct(
        private RenderPaymentResponseAction $render,
        private LoadConfig $config,
    ) {}

    public function handle(Transaction $transaction, array $payload): mixed
    {
        if ($this->config->shouldUseInertia()) {
            return $this->render->renderInertia(
                $transaction,
                $payload,
                $this->config->getPaymentResponseComponent()
            );
        }

        return $this->render->renderBlade($transaction, $payload);
    }
}
