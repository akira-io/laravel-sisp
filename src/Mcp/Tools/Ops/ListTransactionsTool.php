<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Tools\Ops;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Mcp\Concerns\AuthorizesTransactionOps;
use Akira\Sisp\Models\Transaction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('List stored SISP transactions with optional status and date filters.')]
final class ListTransactionsTool extends Tool
{
    use AuthorizesTransactionOps;

    public function handle(Request $request): Response
    {
        $request->validate([
            'status' => ['nullable', 'string'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $denied = $this->denyUnlessAuthorized($request, 'list');

        if ($denied instanceof Response) {
            return $denied;
        }

        $status = $request->get('status');

        if ($status !== null && TransactionStatus::tryFrom((string) $status) === null) {
            return Response::error('Invalid status. Use one of: '.implode(', ', array_column(TransactionStatus::cases(), 'value')));
        }

        $limit = max(1, min(100, (int) ($request->get('limit') ?? 25)));

        $transactions = Transaction::query()
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->when($request->get('from') !== null, fn (Builder $query) => $query->where('created_at', '>=', $this->boundary((string) $request->get('from'), endOfDay: false)))
            ->when($request->get('to') !== null, fn (Builder $query) => $query->where('created_at', '<=', $this->boundary((string) $request->get('to'), endOfDay: true)))
            ->latest()
            ->limit($limit)
            ->get()
            ->filter(fn (Transaction $transaction): bool => $this->isAuthorized($request, 'view', $transaction))
            ->values()
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
            'to' => $schema->string()->description('Only transactions created on or before this moment (ISO 8601). A date without a time includes that whole day.'),
            'limit' => $schema->integer()->description('Maximum rows to return (1-100).')->default(25),
        ];
    }

    private function boundary(string $value, bool $endOfDay): string
    {
        $moment = Date::parse($value)->setTimezone((string) config('app.timezone'));

        if ($endOfDay && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            $moment = $moment->endOfDay();
        }

        return $moment->toDateTimeString();
    }
}
