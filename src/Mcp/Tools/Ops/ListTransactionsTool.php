<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Tools\Ops;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Mcp\Concerns\ResolvesTransaction;
use Akira\Sisp\Models\Transaction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('List stored SISP transactions with optional status and date filters.')]
final class ListTransactionsTool extends Tool
{
    use ResolvesTransaction;

    public function handle(Request $request): Response
    {
        $request->validate([
            'status' => ['nullable', 'string'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $status = $request->get('status');

        if ($status !== null && TransactionStatus::tryFrom((string) $status) === null) {
            return Response::error('Invalid status. Use one of: '.implode(', ', array_column(TransactionStatus::cases(), 'value')));
        }

        $limit = max(1, min(100, (int) ($request->get('limit') ?? 25)));

        $transactions = Transaction::query()
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->when($request->get('from') !== null, fn (Builder $query) => $query->where('created_at', '>=', $request->get('from')))
            ->when($request->get('to') !== null, fn (Builder $query) => $query->where('created_at', '<=', $request->get('to')))
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Transaction $transaction): array => $this->transactionSummary($transaction))
            ->all();

        return Response::json([
            'count' => count($transactions),
            'transactions' => $transactions,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->description('Filter by transaction status.')
                ->enum(array_column(TransactionStatus::cases(), 'value')),
            'from' => $schema->string()->description('Only transactions created on or after this date (ISO 8601).'),
            'to' => $schema->string()->description('Only transactions created on or before this date (ISO 8601).'),
            'limit' => $schema->integer()->description('Maximum rows to return (1-100).')->default(25),
        ];
    }
}
