<?php

declare(strict_types=1);

use Akira\Sisp\Actions\StoreRequestMetadataAction;
use Akira\Sisp\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Stevebauman\Location\Facades\Location;

final class FakeLocationServiceFalse
{
    public function get(): object|false
    {
        return false;
    }
}

it('returns empty geo when location service returns false', function (): void {
    Facade::clearResolvedInstances();
    Location::swap(new FakeLocationServiceFalse());

    $action = resolve(StoreRequestMetadataAction::class);
    $transaction = Transaction::factory()->create();

    $request = Request::create('/geo', 'GET', server: [
        'REMOTE_ADDR' => '1.1.1.1',
        'HTTP_USER_AGENT' => 'Mozilla/5.0',
    ]);

    $meta = $action->handle($request, $transaction);
    expect($meta->country_code)->toBeNull();
});

it('detects Firefox browser', function (): void {
    $action = resolve(StoreRequestMetadataAction::class);
    $transaction = Transaction::factory()->create();

    $request = Request::create('/ua', 'GET', server: [
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:120.0) Gecko/20100101 Firefox/120.0',
    ]);
    $meta = $action->handle($request, $transaction);
    expect($meta->browser)->toBe('Firefox');
});

it('detects Android OS and mobile device', function (): void {
    $action = resolve(StoreRequestMetadataAction::class);
    $transaction = Transaction::factory()->create();

    $request = Request::create('/ua', 'GET', server: [
        'REMOTE_ADDR' => '127.0.0.1',
        // UA that mentions Android but not Linux so Android branch matches
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Android 11; Pixel 4) AppleWebKit/537.36 Mobile Safari/537.36',
    ]);
    $meta = $action->handle($request, $transaction);
    expect($meta->os)->toBe('Android')->and($meta->is_mobile)->toBeTrue();
});

it('detects iOS OS and marks mobile true', function (): void {
    $action = resolve(StoreRequestMetadataAction::class);
    $transaction = Transaction::factory()->create();

    $request = Request::create('/ua', 'GET', server: [
        'REMOTE_ADDR' => '127.0.0.1',
        // UA that contains iPhone/iOS but not 'Mac OS' so iOS branch matches
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU like iOS 16_0) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1',
    ]);
    $meta = $action->handle($request, $transaction);
    expect($meta->os)->toBe('iOS')->and($meta->is_mobile)->toBeTrue();
});
