<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\RenderPaymentResponseBasedOnConfigAction;
use Akira\Sisp\Actions\StoreRequestMetadataAction;
use Akira\Sisp\Exceptions\InvalidPaymentResponseException;
use Akira\Sisp\Facades\Sisp;
use Illuminate\Http\Request;

final readonly class CallbackController
{
    public function __construct(
        private RenderPaymentResponseBasedOnConfigAction $renderResponse,
        private StoreRequestMetadataAction $storeMetadata,
    ) {}

    /**
     * @throws InvalidPaymentResponseException
     */
    public function __invoke(Request $request)
    {
        $payload = $request->all();

        //        if (! Sisp::validateCallback($payload)) {
        //            throw new InvalidPaymentResponseException('Invalid fingerprint signature');
        //        }

        $transaction = Sisp::handlePaymentCallback($payload);

        $this->storeMetadata->handle($request, $transaction);

        return $this->renderResponse->handle($transaction, $payload);
    }
}
