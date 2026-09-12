<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\CancelTransactionAction;
use Akira\Sisp\Actions\RenderPaymentResponseBasedOnConfigAction;
use Akira\Sisp\Actions\StoreRequestMetadataAction;
use Akira\Sisp\Actions\UpdateInvoiceStatusAction;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use LogicException;

final readonly class CallbackController
{
    public function __construct(
        private RenderPaymentResponseBasedOnConfigAction $renderResponse,
        private StoreRequestMetadataAction $storeMetadata,
        private UpdateInvoiceStatusAction $updateInvoiceStatus,
        private LoadConfig $config,
        private CancelTransactionAction $cancelTransaction,
    ) {}

    public function __invoke(Request $request): mixed
    {
        if ($request->boolean('UserCancelled')) {
            return $this->handleUserCancellation($request);
        }

        if ($request->isMethod('get')) {
            return $this->handleGetRequest();
        }

        return $this->handlePostRequest($request);
    }

    private function handleUserCancellation(Request $request): RedirectResponse
    {
        $transaction = $this->resolveCancelledTransaction($request);

        if ($transaction instanceof Transaction) {
            try {
                $this->cancelTransaction->handle($transaction);
                $this->updateInvoiceStatus->handle($transaction, $transaction->status);
            } catch (LogicException) {
            }
        }

        return redirect(config('sisp.redirect_url', '/'));
    }

    private function resolveCancelledTransaction(Request $request): ?Transaction
    {
        $merchantRef = $request->string('merchantRef')->toString();

        if ($merchantRef === '') {
            return null;
        }

        $query = Transaction::query()->where('merchant_ref', $merchantRef);

        $merchantSession = $request->string('merchantSession')->toString();

        if ($merchantSession !== '') {
            $query->where('merchant_session', $merchantSession);
        }

        return $query->first();
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

    private function handlePostRequest(Request $request): RedirectResponse
    {
        $payload = CallbackPayload::from($request->all());

        if ($payload->merchantRef === '' || $payload->merchantSession === '') {
            return redirect(config('sisp.redirect_url', '/'));
        }

        if ($this->isAlreadyProcessed($payload)) {
            return redirect(config('sisp.redirect_url', '/'))->with('info', 'This payment has already been processed.');
        }

        try {
            $transaction = Sisp::handlePaymentCallback($payload);
        } catch (ModelNotFoundException) {
            return redirect(config('sisp.redirect_url', '/'));
        }

        if ($this->config->isMetadataCollectionEnabled()) {
            $this->storeMetadata->handle($request, $transaction);
        }

        $this->updateInvoiceStatus->handle($transaction, $transaction->status);

        return to_route('sisp.callback', ['ref' => $transaction->merchant_ref]);
    }

    private function isAlreadyProcessed(CallbackPayload $payload): bool
    {
        $attempt = TransactionAttempt::query()
            ->where('merchant_ref', $payload->merchantRef)
            ->where('merchant_session', $payload->merchantSession)
            ->where('status', TransactionStatus::completed)
            ->first();

        if ($attempt instanceof TransactionAttempt) {
            return true;
        }

        $transaction = Transaction::query()
            ->where('merchant_ref', $payload->merchantRef)
            ->where('merchant_session', $payload->merchantSession)
            ->first();

        return $transaction instanceof Transaction && $transaction->status === TransactionStatus::completed;
    }
}
