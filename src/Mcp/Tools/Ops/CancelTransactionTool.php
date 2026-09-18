<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Tools\Ops;

use Akira\Sisp\Actions\CancelTransactionAction;
use Akira\Sisp\Mcp\Concerns\AuthorizesTransactionOps;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use LogicException;

#[IsDestructive]
#[Description('Cancel a pending SISP transaction. Completed, failed, refunded, or already-cancelled transactions cannot be cancelled.')]
final class CancelTransactionTool extends Tool
{
    use AuthorizesTransactionOps;

    public function handle(Request $request, CancelTransactionAction $cancel): Response
    {
        $request->validate([
            'transaction' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $transaction = $this->authorizedTransaction($request, 'cancel');

        if ($transaction instanceof Response) {
            return $transaction;
        }

        try {
            $transaction = $cancel->handle($transaction, (string) ($request->get('reason') ?? 'user_cancelled'));
        } catch (LogicException $e) {
            return Response::error('Cancel failed: '.$e->getMessage());
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
                ->description('Transaction id (numeric) or merchant reference to cancel.')
                ->required(),
            'reason' => $schema->string()
                ->description('Reason recorded with the cancellation.'),
        ];
    }
}
