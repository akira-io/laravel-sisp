<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Tools\Ops;

use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Mcp\Concerns\AuthorizesTransactionOps;
use Akira\Sisp\Mcp\Concerns\ThrottlesGatewayCalls;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsIdempotent]
#[Description('Reconcile a stored transaction against the SISP gateway and write the resolved status to it. Safe to repeat, but it changes stored state.')]
final class ReconcileTransactionTool extends Tool
{
    use AuthorizesTransactionOps;
    use ThrottlesGatewayCalls;

    public function handle(Request $request): Response
    {
        $request->validate(['transaction' => ['required', 'string']]);

        $transaction = $this->authorizedTransaction($request, 'reconcile');

        if ($transaction instanceof Response) {
            return $transaction;
        }

        $throttled = $this->throttleGatewayCall($request);

        if ($throttled instanceof Response) {
            return $throttled;
        }

        $transaction = Sisp::reconcileTransactionStatus($transaction);

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
