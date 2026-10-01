<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\BuildPaymentResultUrlAction;
use Akira\Sisp\Actions\CancelTransactionAction;
use Akira\Sisp\Actions\RenderPaymentResponseBasedOnConfigAction;
use Akira\Sisp\Actions\StoreRequestMetadataAction;
use Akira\Sisp\Actions\UpdateInvoiceStatusAction;
use Akira\Sisp\Configuration\CredentialScope;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Contracts\CallbackFingerprintValidator;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Exceptions\UnknownMerchantCredentialsException;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\Pipelines\Callback\Pipes\ValidateFingerprint;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use LogicException;

final readonly class CallbackController
{
    public function __construct(
        private RenderPaymentResponseBasedOnConfigAction $renderResponse,
        private StoreRequestMetadataAction $storeMetadata,
        private UpdateInvoiceStatusAction $updateInvoiceStatus,
        private CancelTransactionAction $cancelTransaction,
        private LoadConfig $config,
        private BuildPaymentResultUrlAction $paymentResultUrl,
        private CredentialScope $credentialScope,
        private Container $container,
    ) {}

    public function __invoke(Request $request): mixed
    {
        if (CallbackPayload::indicatesUserCancellation($request->all())) {
            return $this->handleUserCancellation($request);
        }

        if ($request->isMethod('get')) {
            return $this->handleGetRequest($request);
        }

        return $this->handlePostRequest($request);
    }

    private function handleUserCancellation(Request $request): RedirectResponse
    {
        $transaction = $this->resolveCancelledTransaction($request);

        if ($transaction instanceof Transaction) {
            try {
                $this->cancelTransaction->handle($transaction);
            } catch (LogicException $exception) {
                Log::warning('SISP cancellation callback could not cancel the transaction.', [
                    'transaction_id' => $transaction->getKey(),
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return redirect(config('sisp.redirect_url', '/'));
    }

    private function resolveCancelledTransaction(Request $request): ?Transaction
    {
        $merchantRef = $request->input('merchantRef');
        $merchantSession = $request->input('merchantSession');

        if (! is_string($merchantRef) || ! is_string($merchantSession) || $merchantRef === '' || $merchantSession === '') {
            return null;
        }

        return Transaction::query()
            ->where('merchant_ref', $merchantRef)
            ->where('merchant_session', $merchantSession)
            ->where('status', TransactionStatus::pending->value)
            ->first();
    }

    private function handleGetRequest(Request $request): mixed
    {
        $merchantRef = $request->query('ref');

        if (! $merchantRef || ! $request->hasValidSignature(absolute: false)) {
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

        // A payment built with Sisp::forCredentials() posts back to this same
        // route, so the fingerprint and the pipeline run under the credentials
        // of the transaction the callback names, not the default ones.
        $transaction = Transaction::query()
            ->where('merchant_ref', $payload->merchantRef)
            ->where('merchant_session', $payload->merchantSession)
            ->first();

        if (! $transaction instanceof Transaction) {
            return $this->processCallback($request, $payload);
        }

        try {
            return $this->credentialScope->forTransaction(
                $transaction,
                fn (): RedirectResponse => $this->processCallback($request, $payload),
            );
        } catch (UnknownMerchantCredentialsException $exception) {
            Log::warning('SISP callback rejected: no credentials are known for its merchant.', [
                'merchant_ref' => $payload->merchantRef,
                'pos_id' => $exception->posId,
            ]);

            return redirect(config('sisp.redirect_url', '/'));
        }
    }

    private function processCallback(Request $request, CallbackPayload $payload): RedirectResponse
    {
        if ($this->rejectsFingerprint($payload)) {
            Log::warning('SISP callback rejected: the fingerprint does not match.', [
                'merchant_ref' => $payload->merchantRef,
            ]);

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

        return redirect($this->paymentResultUrl->handle($transaction));
    }

    private function rejectsFingerprint(CallbackPayload $payload): bool
    {
        if (! in_array(ValidateFingerprint::class, $this->config->getCallbackPipes(), true)) {
            return false;
        }

        try {
            // Built here rather than injected, so it hashes the posAutCode of
            // the credentials active for this callback.
            return ! $this->container->make(CallbackFingerprintValidator::class)->handle($payload);
        } catch (InvalidArgumentException) {
            return true;
        }
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
