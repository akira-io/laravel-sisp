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

it('exposes country helpers through facade', function (): void {
    $countries = Sisp::countries();

    expect($countries)->toHaveKey('pt')
        ->and($countries['pt']['numeric'])->toBe('620')
        ->and(Sisp::getCountryNumericCode('PT'))->toBe('620')
        ->and(Sisp::getCountryFlag('PT'))->toBe('https://flagcdn.com/pt.svg')
        ->and(Sisp::getCountryName('PT'))->toBe('Portugal');
});
