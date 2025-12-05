<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\RenderPaymentResponseBasedOnConfigAction;
use Akira\Sisp\Actions\StoreRequestMetadataAction;
use Akira\Sisp\Actions\UpdateInvoiceStatusAction;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Illuminate\Http\Request;

final readonly class CallbackController
{
    public function __construct(
        private RenderPaymentResponseBasedOnConfigAction $renderResponse,
        private StoreRequestMetadataAction $storeMetadata,
        private UpdateInvoiceStatusAction $updateInvoiceStatus,
    ) {}

    public function __invoke(Request $request): mixed
    {

        if ($request->boolean('UserCancelled')) {
            return redirect(config('sisp.redirect_url', '/'));
        }

        if ($request->isMethod('get')) {
            return $this->handleGetRequest();
        }

        return $this->handlePostRequest($request);
    }

    private function handleGetRequest(): mixed
    {
        $merchantRef = request()->query('ref');

        if (! $merchantRef) {
            return redirect(config('sisp.redirect_url', '/'));
        }

        $transaction = Transaction::query()
            ->where('merchant_ref', $merchantRef)->first();

        if (! $transaction) {
            return redirect(config('sisp.redirect_url', '/'));
        }

        if ($transaction->locale) {
            app()->setLocale($transaction->locale);
        }

        return $this->renderResponse->handle($transaction, []);
    }

    private function handlePostRequest(Request $request): \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
    {
        $payload = CallbackPayload::from($request->all());

        $transaction = Sisp::handlePaymentCallback($payload);

        $this->storeMetadata->handle($request, $transaction);

        $this->updateInvoiceStatus->handle($transaction, $transaction->status);

        return to_route('sisp.callback', ['ref' => $transaction->merchant_ref]);
    }
}
