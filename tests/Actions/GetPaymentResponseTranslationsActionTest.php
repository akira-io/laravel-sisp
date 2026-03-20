<?php

declare(strict_types=1);

use Akira\Sisp\Actions\GetPaymentResponseTranslationsAction;

beforeEach(function (): void {
    $this->action = resolve(GetPaymentResponseTranslationsAction::class);
});

it('returns translations array with all required keys', function (): void {
    $translations = $this->action->handle();

    expect($translations)->toBeArray()
        ->toHaveKey('payment')
        ->and($translations['payment'])->toHaveKeys([
            'success_title',
            'success_message',
            'success_status',
            'failed_title',
            'failed_message',
            'failed_status',
            'pending_title',
            'pending_message',
            'pending_status',
            'pending_note',
            'reference',
            'amount',
            'status',
            'category',
            'reason',
            'action',
            'invoice_download',
            'back_home',
            'declined',
            'retry_payment',
            'cancel_payment',
            'copy_reference',
            'download_invoice_alert_title',
            'download_invoice_alert_message',
            'leave_confirmation_title',
            'leave_confirmation_message',
            'leave_page',
            'stay_on_page',
        ]);
});

it('returns string values for all translation keys', function (): void {
    $translations = $this->action->handle();

    foreach ($translations['payment'] as $key => $value) {
        expect($value)->toBeString("Translation key '$key' should be a string");
    }
});

it('returns non-empty strings for all translations', function (): void {
    $translations = $this->action->handle();

    foreach ($translations['payment'] as $key => $value) {
        expect($value)->not->toBeEmpty("Translation key '$key' should not be empty");
    }
});

it('contains retry and cancel payment translations', function (): void {
    $translations = $this->action->handle();

    expect($translations['payment']['retry_payment'])->toBeString()
        ->and($translations['payment']['cancel_payment'])->toBeString();
});
