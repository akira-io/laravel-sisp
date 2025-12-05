<?php

declare(strict_types=1);

use Akira\Sisp\Actions\StoreRequestMetadataAction;
use Akira\Sisp\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Stevebauman\Location\Facades\Location;

final class FakeLocationService
{
    public function get(string $ip): object|false
    {
        if ($ip === '8.8.8.8') {
            return (object) [
                'countryCode' => 'US',
                'countryName' => 'United States',
                'regionName' => 'California',
                'cityName' => 'Mountain View',
                'latitude' => 37.4056,
                'longitude' => -122.0775,
            ];
        }

        return false;
    }
}

it('geocodes public ip when location returns data', function (): void {
    // Swap the Location facade with a simple fake service (no mocks)
    Facade::clearResolvedInstances();
    Location::swap(new FakeLocationService());

    $action = resolve(StoreRequestMetadataAction::class);
    $transaction = Transaction::factory()->create();

    $request = Request::create('/geo', 'GET', server: [
        'REMOTE_ADDR' => '8.8.8.8',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36',
    ]);

    $meta = $action->handle($request, $transaction);

    expect($meta->country_code)->toBe('US')
        ->and($meta->city)->toBe('Mountain View')
        ->and($meta->latitude)->toBeFloat();
});

it('handles unknown UA and sets defaults', function (): void {
    $action = resolve(StoreRequestMetadataAction::class);
    $transaction = Transaction::factory()->create();

    $request = Request::create('/ua', 'GET', server: [
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_USER_AGENT' => 'UA',
    ]);

    $meta = $action->handle($request, $transaction);
    expect(in_array($meta->browser, ['Unknown','Chrome','Firefox','Safari','IE','Edge']))->toBeTrue()
        ->and(in_array($meta->os, ['Unknown','Windows','macOS','Linux','Android','iOS']))->toBeTrue();
});

