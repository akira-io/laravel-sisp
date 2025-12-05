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

    public function renderInertia(PaymentRequest $paymentRequest, string $component = 'Sisp/PaymentForm', ?string $locale = null): mixed
    {
        if (! class_exists(Inertia::class)) {
            return $this->renderBlade($paymentRequest);
        }

        $fields = $paymentRequest->toArray();

        $endpoint = $this->buildFormAction($fields);

        if ($locale) {
            app()->setLocale($locale);
        }

        return Inertia::render($component, [
            'endpoint' => $endpoint,
            'fields' => $fields,
            'translations' => [
                'redirect_title' => __('sisp::payment.redirect_title'),
                'redirect_description' => __('sisp::payment.redirect_description'),
                'secure_transaction' => __('sisp::payment.secure_transaction'),
                'official_portal' => __('sisp::payment.official_portal'),
                'ssl_encryption' => __('sisp::payment.ssl_encryption'),
                'data_protected' => __('sisp::payment.data_protected'),
                'redirecting_in' => trans('sisp::payment.redirecting_in'),
                'connecting' => __('sisp::payment.connecting'),
            ],
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
