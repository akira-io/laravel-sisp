<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Akira\Sisp\ValueObjects\PaymentRequestData;

final readonly class PreparePaymentAction
{
    public function __construct(
        private BuildRequestPayloadAction $buildRequestPayload,
    ) {}

    public function handle(PaymentRequestData $data): PaymentRequest
    {
        return $this->buildRequestPayload->handle($data);
    }
}