<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Tools\Dev;

use Akira\Sisp\Builders\PaymentBuilder;
use Akira\Sisp\Configuration\CredentialScope;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Mcp\Concerns\ResolvesTransaction;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use LogicException;

#[IsReadOnly]
#[Description('Build a signed sandbox SISP callback for a stored transaction, or for a payment shape, so the callback route can be tested locally. Sandbox mode only; does not persist anything or contact the gateway.')]
final class SimulateSandboxCallbackTool extends Tool
{
    use ResolvesTransaction;

    public function __construct(private readonly CredentialScope $credentialScope) {}

    public function handle(Request $request): Response
    {
        $request->validate([
            'transaction' => ['nullable', 'string'],
            'amount' => ['required_without:transaction', 'nullable', 'numeric', 'gt:0'],
            'status' => ['nullable', 'in:success,failed'],
        ]);

        $transaction = $request->get('transaction') === null
            ? null
            : $this->resolveTransaction((string) $request->get('transaction'));

        if ($transaction instanceof Response) {
            return $transaction;
        }

        $builder = $transaction instanceof Transaction
            ? $this->builderForStoredTransaction($transaction)
            : resolve(PaymentBuilder::class)->amount((float) $request->get('amount'));

        foreach (['currency', 'locale', 'customerEmail'] as $field) {
            $value = $request->get($field);

            if ($value !== null) {
                $builder->{$field}((string) $value);
            }
        }

        $generate = fn (): CallbackPayload => Sisp::generateSandboxPayload($builder->toData(), (string) ($request->get('status') ?? 'success'));

        try {
            $payload = $transaction instanceof Transaction
                ? $this->credentialScope->forTransaction($transaction, $generate)
                : $generate();
        } catch (LogicException $e) {
            return Response::error('Could not build sandbox payload: '.$e->getMessage());
        }

        return Response::json([
            'status' => $request->get('status') ?? 'success',
            'callback' => $payload->toArray(),
            'note' => 'POST these fields to the /sisp/callback route to exercise the callback pipeline.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'transaction' => $schema->string()
                ->description('Stored transaction id or merchant reference to answer. Its reference, session, amount and currency are used, so the callback reaches it.'),
            'amount' => $schema->number()
                ->description('Payment amount in major currency units, e.g. 1500.00. Required without a transaction.'),
            'status' => $schema->string()
                ->description('Outcome to simulate.')
                ->enum(['success', 'failed'])
                ->default('success'),
            'currency' => $schema->string()
                ->description('ISO 4217 numeric currency code. Defaults to the configured currency.'),
            'locale' => $schema->string()
                ->description('Locale for the payment, e.g. "pt".'),
            'customerEmail' => $schema->string()
                ->description('Customer email to embed in the payload.'),
        ];
    }

    private function builderForStoredTransaction(Transaction $transaction): PaymentBuilder
    {
        $builder = resolve(PaymentBuilder::class)
            ->amount((float) $transaction->amount)
            ->merchantRef($transaction->merchant_ref)
            ->merchantSession($transaction->currentAttempt->merchant_session ?? $transaction->merchant_session);

        foreach (['currency' => 'currency', 'transactionCode' => 'transaction_code'] as $method => $column) {
            $value = (string) $transaction->getAttribute($column);

            if ($value !== '') {
                $builder->{$method}($value);
            }
        }

        return $builder;
    }
}
