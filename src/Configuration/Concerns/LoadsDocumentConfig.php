<?php

declare(strict_types=1);

namespace Akira\Sisp\Configuration\Concerns;

trait LoadsDocumentConfig
{
    public function getTransactionStatusUrl(): string
    {
        return $this->config->get('sisp.transaction_status.url', 'https://comerciante.vinti4.cv/pos/transaction-status');
    }

    public function getTransactionStatusPortalId(): string
    {
        return $this->config->get('sisp.transaction_status.portal_id', '');
    }

    public function getTransactionStatusPortalPassword(): string
    {
        return $this->config->get('sisp.transaction_status.portal_password', '');
    }

    public function getTransactionStatusTimeoutSeconds(): int
    {
        return (int) $this->config->get('sisp.transaction_status.timeout_seconds', 10);
    }

    public function getInvoiceNumberFormat(): string
    {
        return $this->config->get('sisp.invoice.number_format', 'date-based');
    }

    public function getInvoiceNumberPrefix(): string
    {
        return $this->config->get('sisp.invoice.prefix', 'INV');
    }

    public function getInvoiceStorageDisk(): string
    {
        return $this->config->get('sisp.invoice.disk', 'public');
    }

    public function getInvoiceTemplate(): string
    {
        return $this->config->get('sisp.invoice.template', 'branded');
    }

    public function getInvoiceCompanyName(): string
    {
        return $this->config->get('sisp.invoice.company_name', '');
    }

    public function getInvoiceCompanyAddress(): string
    {
        return $this->config->get('sisp.invoice.company_address', '');
    }

    public function getInvoiceCompanyCode(): string
    {
        return $this->config->get('sisp.invoice.company_code', '');
    }

    public function getInvoiceCompanyCountry(): string
    {
        return $this->config->get('sisp.invoice.company_country', '');
    }

    public function getInvoiceCompanyPhone(): string
    {
        return $this->config->get('sisp.invoice.company_phone', '');
    }

    public function getInvoiceCompanyEmail(): string
    {
        return $this->config->get('sisp.invoice.company_email', '');
    }

    public function getInvoiceCompanyWebsite(): string
    {
        return $this->config->get('sisp.invoice.company_website', '');
    }
}
