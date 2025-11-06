<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\ValueObjects\PaymentRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Uri;
use Inertia\Inertia;

final readonly class RenderPaymentFormAction
{
    public function __construct(
        private DeterminePaymentEndpointAction $determineEndpoint,
    ) {}

    public function renderBlade(PaymentRequest $paymentRequest): View
    {
        $fields = $paymentRequest->toArray();
        $formAction = $this->buildFormAction($fields);

        return view('sisp::payment-form', [
            'formAction' => $formAction,
            'fields' => $fields,
        ]);
    }

    public function renderInertia(PaymentRequest $paymentRequest, string $component = 'Sisp/PaymentForm'): mixed
    {
        if (! class_exists('Inertia\Inertia')) {
            return $this->renderBlade($paymentRequest);
        }

        $fields = $paymentRequest->toArray();
        $endpoint = $this->buildFormAction($fields);

        return Inertia::render($component, [
            'endpoint' => $endpoint,
            'fields' => $fields,
        ]);
    }

    private function buildFormAction(array $fields): string
    {
        return (string) Uri::of($this->determineEndpoint->handle())
            ->withQuery(['FingerPrint' => $fields['fingerprint']])
            ->withQuery(['TimeStamp' => $fields['timeStamp']])
            ->withQuery(['FingerPrintVersion' => $fields['fingerprintversion']]);
    }
}
