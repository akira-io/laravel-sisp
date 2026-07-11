<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Tools\Ops;

use Akira\Sisp\Actions\CancelTransactionAction;
use Akira\Sisp\Mcp\Concerns\AuthorizesTransactionOps;
use Akira\Sisp\Mcp\Concerns\ResolvesTransaction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Throwable;

#[IsDestructive]
#[Description('Cancel a pending SISP transaction. Completed or already-cancelled transactions cannot be cancelled.')]
final class CancelTransactionTool extends Tool
{
    use AuthorizesTransactionOps;
    use ResolvesTransaction;

    public function handle(Request $request, CancelTransactionAction $cancel): Response
    {
        $request->validate([
            'transaction' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $transaction = $this->resolveTransaction((string) $request->get('transaction'));

        if (! $transaction instanceof \Akira\Sisp\Models\Transaction) {
            return Response::error('No transaction found for "'.$request->get('transaction').'".');
        }

        if (! $this->isAuthorized($request, $transaction)) {
            return Response::error('Not authorized to cancel this transaction.');
        }

        try {
            $transaction = $cancel->handle($transaction, (string) ($request->get('reason') ?? 'user_cancelled'));
        } catch (Throwable $e) {
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
