<?php

declare(strict_types=1);

use Akira\Sisp\Support\Countries;

it('returns all countries with correct structure', function (): void {
    $countries = Countries::all();

    expect($countries)->toBeArray()
        ->and(array_keys($countries))->toContain('cv', 'pt', 'br', 'us')
        ->and($countries['cv'])->toHaveKeys(['alpha2', 'numeric', 'name', 'flag'])
        ->and($countries['cv']['alpha2'])->toBe('CV')
        ->and($countries['cv']['numeric'])->toBe('132')
        ->and($countries['cv']['name'])->toBe('Cabo Verde')
        ->and($countries['cv']['flag'])->toBe('https://flagcdn.com/cv.svg');
});

it('returns numeric code for known countries', function (): void {
    expect(Countries::getNumericCode('CV'))->toBe('132')
        ->and(Countries::getNumericCode('PT'))->toBe('620')
        ->and(Countries::getNumericCode('BR'))->toBe('076')
        ->and(Countries::getNumericCode('US'))->toBe('840');
});

it('is case insensitive when getting numeric code', function (): void {
    expect(Countries::getNumericCode('cv'))->toBe('132')
        ->and(Countries::getNumericCode('Cv'))->toBe('132')
        ->and(Countries::getNumericCode('CV'))->toBe('132');
});

it('returns default numeric code for unknown countries', function (): void {
    expect(Countries::getNumericCode('XX'))->toBe('132')
        ->and(Countries::getNumericCode('ZZ'))->toBe('132');
});

it('returns flag url for known countries', function (): void {
    expect(Countries::getFlag('CV'))->toBe('https://flagcdn.com/cv.svg')
        ->and(Countries::getFlag('PT'))->toBe('https://flagcdn.com/pt.svg')
        ->and(Countries::getFlag('BR'))->toBe('https://flagcdn.com/br.svg');
});

it('is case insensitive when getting flag', function (): void {
    expect(Countries::getFlag('cv'))->toBe('https://flagcdn.com/cv.svg')
        ->and(Countries::getFlag('Cv'))->toBe('https://flagcdn.com/cv.svg')
        ->and(Countries::getFlag('CV'))->toBe('https://flagcdn.com/cv.svg');
});

it('returns default flag for unknown countries', function (): void {
    expect(Countries::getFlag('XX'))->toBe('https://flagcdn.com/xx.svg')
        ->and(Countries::getFlag('ZZ'))->toBe('https://flagcdn.com/xx.svg');
});

it('returns country name for known countries', function (): void {
    expect(Countries::getName('CV'))->toBe('Cabo Verde')
        ->and(Countries::getName('PT'))->toBe('Portugal')
        ->and(Countries::getName('BR'))->toBe('Brazil')
        ->and(Countries::getName('US'))->toBe('United States');
});

it('is case insensitive when getting name', function (): void {
    expect(Countries::getName('cv'))->toBe('Cabo Verde')
        ->and(Countries::getName('Cv'))->toBe('Cabo Verde')
        ->and(Countries::getName('CV'))->toBe('Cabo Verde');
});

it('returns null for unknown country names', function (): void {
    expect(Countries::getName('XX'))->toBeNull()
        ->and(Countries::getName('ZZ'))->toBeNull();
});

it('finds country by numeric code', function (): void {
    $country = Countries::findByNumeric('132');

    expect($country)->toBeArray()
        ->and($country['alpha2'])->toBe('CV')
        ->and($country['numeric'])->toBe('132')
        ->and($country['name'])->toBe('Cabo Verde')
        ->and($country['flag'])->toBe('https://flagcdn.com/cv.svg');
});

it('returns null when country not found by numeric code', function (): void {
    expect(Countries::findByNumeric('999'))->toBeNull()
        ->and(Countries::findByNumeric('000'))->toBeNull();
});
