<?php

declare(strict_types=1);

use Akira\Sisp\Actions\GetPaymentErrorResponseAction;
use Akira\Sisp\Enums\ErrorMessageType;
use Akira\Sisp\ValueObjects\PaymentErrorResponse;

beforeEach(function (): void {
    $this->action = resolve(GetPaymentErrorResponseAction::class);
});

it('transforms error message type to payment error response', function (): void {
    $errorType = ErrorMessageType::cardExpired;

    $response = $this->action->handle($errorType);

    expect($response)->toBeInstanceOf(PaymentErrorResponse::class)
        ->and($response->code)->toBe('33')
        ->and($response->label)->toBeString()
        ->and($response->category)->toBeString()
        ->and($response->categoryLabel)->toBeString()
        ->and($response->action)->toBeString()
        ->and($response->actionLabel)->toBeString();
});

it('converts payment error response to array', function (): void {
    $errorType = ErrorMessageType::insufficientFunds;

    $response = $this->action->handle($errorType);
    $array = $response->toArray();

    expect($array)->toBeArray()
        ->and($array['code'])->toBe('51')
        ->and($array['label'])->toBeString()
        ->and($array['category'])->toBeString()
        ->and($array['categoryLabel'])->toBeString()
        ->and($array['action'])->toBeString()
        ->and($array['actionLabel'])->toBeString();
});

it('handles all error message types', function (): void {
    $errorTypes = ErrorMessageType::cases();

    foreach ($errorTypes as $errorType) {
        $response = $this->action->handle($errorType);

        expect($response)->toBeInstanceOf(PaymentErrorResponse::class)
            ->and($response->code)->toBe($errorType->value)
            ->and($response->label)->toBeString()
            ->and($response->category)->toBeString()
            ->and($response->categoryLabel)->toBeString()
            ->and($response->action)->toBeString()
            ->and($response->actionLabel)->toBeString();
    }
});
