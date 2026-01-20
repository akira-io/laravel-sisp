<?php

declare(strict_types=1);

use Akira\Sisp\Support\CountryCodeMapper;

it('converts known ISO alpha-2 codes to numeric codes', function (): void {
    expect(CountryCodeMapper::toNumeric('CV'))->toBe('132')
        ->and(CountryCodeMapper::toNumeric('PT'))->toBe('620')
        ->and(CountryCodeMapper::toNumeric('BR'))->toBe('076')
        ->and(CountryCodeMapper::toNumeric('ES'))->toBe('724')
        ->and(CountryCodeMapper::toNumeric('FR'))->toBe('250')
        ->and(CountryCodeMapper::toNumeric('DE'))->toBe('276')
        ->and(CountryCodeMapper::toNumeric('GB'))->toBe('826')
        ->and(CountryCodeMapper::toNumeric('US'))->toBe('840');
});

it('is case insensitive', function (): void {
    expect(CountryCodeMapper::toNumeric('cv'))->toBe('132')
        ->and(CountryCodeMapper::toNumeric('Cv'))->toBe('132')
        ->and(CountryCodeMapper::toNumeric('cV'))->toBe('132')
        ->and(CountryCodeMapper::toNumeric('CV'))->toBe('132');
});

it('returns default code for unknown countries', function (): void {
    expect(CountryCodeMapper::toNumeric('XX'))->toBe('132')
        ->and(CountryCodeMapper::toNumeric('ZZ'))->toBe('132')
        ->and(CountryCodeMapper::toNumeric('INVALID'))->toBe('132');
});
