<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RenderPaymentResponseAction;
use Akira\Sisp\Models\Transaction;

it('getStructuredError returns null for unknown message type', function (): void {
    $t = Transaction::factory()->create([
        'message_type' => 'XYZ',
    ]);

    $view = resolve(RenderPaymentResponseAction::class)->renderBlade($t, []);
    expect($view->name())->toBe('sisp::payment-response');
});
