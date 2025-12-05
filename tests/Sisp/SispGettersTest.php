<?php

declare(strict_types=1);

use Akira\Sisp\Facades\Sisp;

it('exposes simple getters through facade', function (): void {
    expect(Sisp::getMerchantReference())->toBeString()
        ->and(Sisp::getMerchantSession())->toBeString()
        ->and(Sisp::getTimeStamp())->toBeString()
        ->and(Sisp::getCurrency())->toBeString()
        ->and(Sisp::getPosId())->toBeString()
        ->and(Sisp::getPosAutCode())->toBeString()
        ->and(Sisp::getIs3Dsec())->toBeString()
        ->and(Sisp::getUrlMerchantResponse())->toBeString()
        ->and(Sisp::getLanguageMessages())->toBeString()
        ->and(Sisp::getFingerprintVersion())->toBeString()
        ->and(Sisp::getDefaultTransactionCode())->toBeString()
        ->and(Sisp::getUri())->toBeString();
});

