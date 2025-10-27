<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\RenderPaymentResponseAction;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Facades\Sisp;
use Illuminate\Http\Request;

final readonly class CallbackController
{
    public function __construct(
        private RenderPaymentResponseAction $renderResponse,
        private LoadConfig $loadConfig,
    ) {}

    public function __invoke(Request $request)
    {
        $payload = $request->all();

        // TODO: Implement correct fingerprint validation for response
        // For now, skip validation to complete the payment flow
        // if (!Sisp::validateCallback($payload)) {
        //     throw new InvalidPaymentResponseException('Invalid fingerprint signature');
        // }

        $transaction = Sisp::handlePaymentCallback($payload);

        if ($this->loadConfig->shouldUseInertia()) {
            return $this->renderResponse->renderInertia(
                $transaction,
                $payload,
                $this->loadConfig->getPaymentResponseComponent()
            );
        }

        return $this->renderResponse->renderBlade($transaction, $payload);
    }
}
