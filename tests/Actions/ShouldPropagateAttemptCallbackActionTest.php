<?php

declare(strict_types=1);

use Akira\Sisp\Actions\Transaction\ShouldPropagateAttemptCallbackAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\TransactionAttempt;

it('propagates current attempts and completed superseded attempts only', function (): void {
    $action = resolve(ShouldPropagateAttemptCallbackAction::class);

    $currentAttempt = TransactionAttempt::factory()->create();
    $supersededAttempt = TransactionAttempt::factory()->create([
        'superseded_at' => now(),
    ]);

    expect($action->handle($currentAttempt, TransactionStatus::failed))->toBeTrue()
        ->and($action->handle($supersededAttempt, TransactionStatus::completed))->toBeTrue()
        ->and($action->handle($supersededAttempt, TransactionStatus::pending))->toBeFalse()
        ->and($action->handle($supersededAttempt, TransactionStatus::failed))->toBeFalse();
});
