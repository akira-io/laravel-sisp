<?php

declare(strict_types=1);

use Akira\Sisp\Http\Middleware\ProtectPaymentRoute;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;

it('uses configurable middleware for payment and retry routes', function (): void {
    config()->set('sisp.middleware.payment', ['web', 'auth', ProtectPaymentRoute::class]);
    config()->set('sisp.middleware.retry', ['web', 'auth']);

    withReloadedSispRoutes(function (): void {
        $paymentMiddleware = Route::getRoutes()->getByName('sisp.payment')->gatherMiddleware();
        $retryMiddleware = Route::getRoutes()->getByName('sisp.retry-payment')->gatherMiddleware();

        expect($paymentMiddleware)->toContain('web', 'auth', ProtectPaymentRoute::class)
            ->and($retryMiddleware)->toContain('web', 'auth');
    });
});

it('keeps callback route outside configurable browser middleware', function (): void {
    config()->set('sisp.middleware.payment', ['web', 'auth', ProtectPaymentRoute::class]);
    config()->set('sisp.middleware.retry', ['web', 'auth']);
    config()->set('sisp.middleware.refund', ['web', 'auth']);

    withReloadedSispRoutes(function (): void {
        $callbackRoute = Route::getRoutes()->getByName('sisp.callback');

        expect($callbackRoute->gatherMiddleware())->not->toContain('auth')
            ->and($callbackRoute->excludedMiddleware())->toContain('web');
    });
});

function withReloadedSispRoutes(callable $callback): void
{
    $router = Route::getFacadeRoot();
    $originalRoutes = $router->getRoutes();

    try {
        $router->setRoutes(new RouteCollection());
        require __DIR__.'/../../routes/web.php';
        Route::getRoutes()->refreshNameLookups();

        $callback();
    } finally {
        $router->setRoutes($originalRoutes);
    }
}
