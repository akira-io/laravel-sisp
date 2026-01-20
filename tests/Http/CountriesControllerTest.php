<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;

it('returns all countries as json', function (): void {
    $response = $this->get(route('sisp.countries'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonStructure([
            'cv' => ['alpha2', 'numeric', 'name', 'flag'],
            'pt' => ['alpha2', 'numeric', 'name', 'flag'],
            'br' => ['alpha2', 'numeric', 'name', 'flag'],
        ])
        ->assertJsonFragment([
            'alpha2' => 'CV',
            'numeric' => '132',
            'name' => 'Cabo Verde',
            'flag' => 'https://flagcdn.com/cv.svg',
        ]);
});

it('caches countries data forever', function (): void {
    Cache::flush();

    expect(Cache::has('sisp.countries'))->toBeFalse();

    $this->get(route('sisp.countries'));

    expect(Cache::has('sisp.countries'))->toBeTrue()
        ->and(Cache::get('sisp.countries'))->toBeArray()
        ->and(Cache::get('sisp.countries'))->toHaveKey('cv');
});
