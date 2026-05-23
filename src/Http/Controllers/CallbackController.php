<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\ReconcileTransactionStatusAction;
use Akira\Sisp\Actions\RenderPaymentResponseBasedOnConfigAction;
use Akira\Sisp\Actions\StoreRequestMetadataAction;
use Akira\Sisp\Actions\UpdateInvoiceStatusAction;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class CallbackController
{
    public function __construct(
        private RenderPaymentResponseBasedOnConfigAction $renderResponse,
        private StoreRequestMetadataAction $storeMetadata,
        private UpdateInvoiceStatusAction $updateInvoiceStatus,
        private ReconcileTransactionStatusAction $reconcileTransactionStatus,
        private LoadConfig $config,
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

        $transaction = $this->reconcileIfIndeterminate($transaction);

        return $this->renderResponse->handle($transaction, []);
    }

    private function reconcileIfIndeterminate(Transaction $transaction): Transaction
    {
        if (! $this->config->isTransactionStatusReconciliationEnabled()) {
            return $transaction;
        }

        $createdAt = $transaction->getAttribute('created_at');

        if (
            $transaction->status === TransactionStatus::pending
            && blank($transaction->getAttribute('message_type'))
            && $createdAt instanceof CarbonInterface
            && $createdAt->lte(now()->subMinutes($this->config->getTransactionStatusIndeterminateAfterMinutes()))
        ) {
            return $this->reconcileTransactionStatus->handle($transaction);
        }

        return $transaction;
    }

    private function handlePostRequest(Request $request): RedirectResponse
    {
        $payload = CallbackPayload::from($request->all());

        if (! Sisp::validateCallback($payload)) {
            return redirect(config('sisp.redirect_url', '/'));
        }

        if ($payload->merchantRef === '' || $payload->merchantSession === '') {
            return redirect(config('sisp.redirect_url', '/'));
        }

        if ($this->isAlreadyProcessed($payload)) {
            return redirect(config('sisp.redirect_url', '/'))->with('info', 'This payment has already been processed.');
        }

        $transaction = Sisp::handlePaymentCallback($payload);

        $this->storeMetadata->handle($request, $transaction);

        $this->updateInvoiceStatus->handle($transaction, $transaction->status);

        return to_route('sisp.callback', ['ref' => $transaction->merchant_ref]);
    }

    private function isAlreadyProcessed(CallbackPayload $payload): bool
    {
        $transaction = Transaction::query()
            ->where('merchant_ref', $payload->merchantRef)
            ->where('merchant_session', $payload->merchantSession)
            ->first();

        return $transaction !== null && $transaction->getAttribute('transaction_id') !== null;
    }
}
