<?php

declare(strict_types=1);

use Akira\Sisp\Exceptions\BlacklistedIdentifierException;
use Akira\Sisp\Exceptions\InvalidPaymentResponseException;
use Akira\Sisp\Exceptions\RateLimitExceededException;
use Akira\Sisp\Exceptions\TransactionNotFoundException;

it('instantiates custom exceptions with default codes', function (): void {
    $e1 = new RateLimitExceededException();
    $e2 = new TransactionNotFoundException();
    $e3 = new InvalidPaymentResponseException();
    $e4 = new BlacklistedIdentifierException('Blocked');

    expect($e1->getCode())->toBe(429)
        ->and($e1->getMessage())->not->toBe('')
        ->and($e2->getMessage())->not->toBe('')
        ->and($e3->getMessage())->not->toBe('')
        ->and($e4->getMessage())->toBe('Blocked');
});
