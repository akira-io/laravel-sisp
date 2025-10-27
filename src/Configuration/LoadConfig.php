<?php

declare(strict_types=1);

namespace Akira\Sisp\Configuration;

use Illuminate\Contracts\Foundation\Application;

final readonly class LoadConfig
{
    public function __construct(
        private Application $app,
    ) {}

    public function shouldUseInertia(): bool
    {
        return $this->app['config']->get('sisp.use_inertia.enabled', false) && class_exists('Inertia\Inertia');
    }

    public function shouldUseBlade(): bool
    {
        return $this->app['config']->get('sisp.use_blade.enabled', true);
    }

    public function getPaymentFormComponent(): string
    {
        return $this->app['config']->get('sisp.use_inertia.payment_form_component', 'Sisp/PaymentForm');
    }

    public function getPaymentResponseComponent(): string
    {
        return $this->app['config']->get('sisp.use_inertia.payment_response_component', 'Sisp/PaymentResponse');
    }

    public function getPaymentFormView(): string
    {
        return $this->app['config']->get('sisp.use_blade.payment_form', 'sisp::payment-form');
    }

    public function getPaymentResponseView(): string
    {
        return $this->app['config']->get('sisp.use_blade.payment_response', 'sisp::payment-response');
    }

    public function isSandboxEnabled(): bool
    {
        return $this->app['config']->get('sisp.sandbox', false);
    }

    public function getMerchantReference(): string
    {
        $configured = $this->app['config']->get('sisp.merchant_ref');

        return $configured ?? 'R'.date('YmdHis');
    }

    public function getMerchantSession(): string
    {
        $configured = $this->app['config']->get('sisp.merchant_session');

        return $configured ?? 'S'.date('YmdHis');
    }

    public function getTimeStamp(): string
    {
        return date('Y-m-d H:i:s');
    }

    public function getCurrency(): string
    {
        return $this->app['config']->get('sisp.currency', '132');
    }

    public function getPosId(): string
    {
        return $this->app['config']->get('sisp.posID', '');
    }

    public function getPosAutCode(): string
    {
        return $this->app['config']->get('sisp.posAutCode', '');
    }

    public function getIs3Dsec(): string
    {
        return $this->app['config']->get('sisp.is_3dsec', '0');
    }

    public function getUrlMerchantResponse(): string
    {
        $configured = $this->app['config']->get('sisp.url_merchant_response');

        return $configured ?? route('sisp.callback');
    }

    public function getLanguageMessages(): string
    {
        return $this->app['config']->get('sisp.language_messages', 'EN');
    }

    public function getFingerprintVersion(): string
    {
        return $this->app['config']->get('sisp.fingerprint_version', '1');
    }

    public function getDefaultTransactionCode(): string
    {
        return $this->app['config']->get('sisp.transaction_code', '1');
    }

    public function getUri(): string
    {
        return $this->app['config']->get('sisp.url', '');
    }

    public function getInvoiceNumberFormat(): string
    {
        return $this->app['config']->get('sisp.invoice.number_format', 'date-based');
    }

    public function getInvoiceNumberPrefix(): string
    {
        return $this->app['config']->get('sisp.invoice.prefix', 'INV');
    }
}
