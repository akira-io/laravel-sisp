<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Tools\Ops;

use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Mcp\Concerns\ResolvesTransaction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Throwable;

#[IsIdempotent]
#[Description('Reconcile a stored transaction against the SISP gateway and persist the resolved status. Safe to call repeatedly.')]
final class ReconcileTransactionTool extends Tool
{
    use ResolvesTransaction;

    public function handle(Request $request): Response
    {
        $request->validate(['transaction' => ['required', 'string']]);

        $transaction = $this->resolveTransaction((string) $request->get('transaction'));

        if (! $transaction instanceof \Akira\Sisp\Models\Transaction) {
            return Response::error('No transaction found for "'.$request->get('transaction').'".');
        }

        try {
            $transaction = Sisp::reconcileTransactionStatus($transaction);
        } catch (Throwable $e) {
            return Response::error('Could not reconcile transaction: '.$e->getMessage());
        }

        return Response::json($this->transactionSummary($transaction));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'transaction' => $schema->string()
                ->description('Transaction id (numeric) or merchant reference to reconcile.')
                ->required(),
        ];
    }
}
