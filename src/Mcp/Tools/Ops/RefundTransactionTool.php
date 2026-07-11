<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Tools\Ops;

use Akira\Sisp\Facades\Sisp;
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
#[Description('Refund a completed SISP transaction, fully or partially. This moves money and cannot be undone.')]
final class RefundTransactionTool extends Tool
{
    use AuthorizesTransactionOps;
    use ResolvesTransaction;

    public function handle(Request $request): Response
    {
        $request->validate([
            'transaction' => ['required', 'string'],
            'amount' => ['nullable', 'numeric', 'gt:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $transaction = $this->resolveTransaction((string) $request->get('transaction'));

        if (! $transaction instanceof \Akira\Sisp\Models\Transaction) {
            return Response::error('No transaction found for "'.$request->get('transaction').'".');
        }

        if (! $this->isAuthorized($request, $transaction)) {
            return Response::error('Not authorized to refund this transaction.');
        }

        $builder = Sisp::refund($transaction);

        $amount = $request->get('amount');
        $amount !== null ? $builder->amount((float) $amount) : $builder->full();

        if ($request->get('reason') !== null) {
            $builder->reason((string) $request->get('reason'));
        }

        try {
            $transaction = $builder->process();
        } catch (Throwable $e) {
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
                ->description('Amount to refund in major currency units. Omit to refund the full amount.'),
            'reason' => $schema->string()
                ->description('Reason recorded with the refund.'),
        ];
    }
}
