<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Tools\Ops;

use Akira\Sisp\Actions\RecordRefundAction;
use Akira\Sisp\Http\Requests\RefundTransactionRequest;
use Akira\Sisp\Mcp\Concerns\AuthorizesTransactionOps;
use Akira\Sisp\Mcp\Concerns\ThrottlesToolCalls;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use LogicException;

#[IsDestructive]
#[Description('Record a refund against a completed SISP transaction, fully or partially, by an explicit amount. The package never sends refunds to SISP: issue the refund in the SISP back office first, then record it here. Recording changes the stored balance and status and cannot be undone.')]
final class RefundTransactionTool extends Tool
{
    use AuthorizesTransactionOps;
    use ThrottlesToolCalls;

    public function handle(Request $request, RecordRefundAction $refund): Response
    {
        $validated = $request->validate([
            'transaction' => ['required', 'string'],
            ...new RefundTransactionRequest()->rules(),
            'idempotency_key' => ['required', 'string', 'max:255'],
        ]);

        $transaction = $this->authorizedTransaction($request, 'refund');

        if ($transaction instanceof Response) {
            return $transaction;
        }

        $throttled = $this->throttleToolCall($request, 'destructive', 'refund records');

        if ($throttled instanceof Response) {
            return $throttled;
        }

        try {
            $transaction = $refund->handle(
                $transaction,
                (float) $validated['amount'],
                (string) ($validated['reason'] ?? 'user_refund'),
                isset($validated['idempotency_key']) ? (string) $validated['idempotency_key'] : null,
            );
        } catch (LogicException $e) {
            return Response::error('Refund failed: '.$e->getMessage());
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
                ->description('Transaction id (numeric) or merchant reference to refund.')
                ->required(),
            'amount' => $schema->number()
                ->description('Amount to refund in major currency units. Pass the full amount for a total refund.')
                ->required(),
            'reason' => $schema->string()
                ->description('Reason recorded with the refund.'),
            'idempotency_key' => $schema->string()
                ->description('Key that makes a retried record safe: repeating the call with the same key and amount records one refund. Reuse the same key when retrying after a timeout, and never reuse it for a different amount.')
                ->required(),
        ];
    }
}
