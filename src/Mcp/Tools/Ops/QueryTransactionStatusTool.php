<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Tools\Ops;

use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Mcp\Concerns\AuthorizesTransactionOps;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsIdempotent]
#[Description('Query the live status of a stored transaction at the SISP gateway by transaction id or merchant reference.')]
final class QueryTransactionStatusTool extends Tool
{
    use AuthorizesTransactionOps;

    public function handle(Request $request): Response
    {
        $request->validate(['transaction' => ['required', 'string']]);

        $transaction = $this->authorizedTransaction($request, 'query');

        if ($transaction instanceof Response) {
            return $transaction;
        }

        $status = Sisp::queryTransactionStatus($transaction);

        return Response::json([
            'result' => $status->result,
            'transaction_success' => $status->transactionSuccess,
            'payment_status' => $status->paymentStatus()->value,
            'description' => $status->transactionStatusDescription,
            'message' => $status->message,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'transaction' => $schema->string()
                ->description('Transaction id (numeric) or merchant reference to query at SISP.')
                ->required(),
        ];
    }
}
