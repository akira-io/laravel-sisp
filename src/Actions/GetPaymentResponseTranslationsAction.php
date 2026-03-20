<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

final readonly class GetPaymentResponseTranslationsAction
{
    public function handle(): array
    {
        return [
            'payment' => [
                'success_title' => __('sisp::messages.payment.response.success_title'),
                'success_message' => __('sisp::messages.payment.response.success_message'),
                'success_status' => __('sisp::messages.payment.response.success_status'),
                'failed_title' => __('sisp::messages.payment.response.failed_title'),
                'failed_message' => __('sisp::messages.payment.response.failed_message'),
                'failed_status' => __('sisp::messages.payment.response.failed_status'),
                'pending_title' => __('sisp::messages.payment.response.pending_title'),
                'pending_message' => __('sisp::messages.payment.response.pending_message'),
                'pending_status' => __('sisp::messages.payment.response.pending_status'),
                'pending_note' => __('sisp::messages.payment.response.pending_note'),
                'reference' => __('sisp::messages.payment.response.reference'),
                'amount' => __('sisp::messages.payment.response.amount'),
                'status' => __('sisp::messages.payment.response.status'),
                'category' => __('sisp::messages.payment.response.category'),
                'reason' => __('sisp::messages.payment.response.reason'),
                'action' => __('sisp::messages.payment.response.action'),
                'invoice_download' => __('sisp::messages.payment.response.invoice_download'),
                'back_home' => __('sisp::messages.payment.response.back_home'),
                'declined' => __('sisp::messages.payment.response.declined'),
                'retry_payment' => __('sisp::messages.payment.response.retry_payment'),
                'cancel_payment' => __('sisp::messages.payment.response.cancel_payment'),
                'copy_reference' => __('sisp::messages.payment.response.copy_reference'),
                'download_invoice_alert_title' => __('sisp::messages.payment.response.download_invoice_alert_title'),
                'download_invoice_alert_message' => __('sisp::messages.payment.response.download_invoice_alert_message'),
                'leave_confirmation_title' => __('sisp::messages.payment.response.leave_confirmation_title'),
                'leave_confirmation_message' => __('sisp::messages.payment.response.leave_confirmation_message'),
                'leave_page' => __('sisp::messages.payment.response.leave_page'),
                'stay_on_page' => __('sisp::messages.payment.response.stay_on_page'),
            ],
        ];
    }
}
