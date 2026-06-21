<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Servers;

use Akira\Sisp\Mcp\Tools\Ops\BuildPaymentRequestTool;
use Akira\Sisp\Mcp\Tools\Ops\CancelTransactionTool;
use Akira\Sisp\Mcp\Tools\Ops\GetTransactionTool;
use Akira\Sisp\Mcp\Tools\Ops\ListTransactionsTool;
use Akira\Sisp\Mcp\Tools\Ops\QueryTransactionStatusTool;
use Akira\Sisp\Mcp\Tools\Ops\ReconcileTransactionTool;
use Akira\Sisp\Mcp\Tools\Ops\RefundTransactionTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Override;

#[Name('sisp-ops')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
    Runtime operations for the akira/laravel-sisp payment gateway.

    Use this server to build payment request payloads, query and reconcile
    transaction status against the live SISP gateway, list and inspect stored
    transactions, and refund or cancel transactions.

    Refund and cancel move money and change state: they are irreversible. Confirm
    the transaction identifier and amount with a human before calling them. Over
    the web transport these destructive tools are hidden unless the host
    application explicitly opts in.
    MARKDOWN)]
final class SispOpsServer extends Server
{
    #[Override]
    protected array $tools = [
        BuildPaymentRequestTool::class,
        QueryTransactionStatusTool::class,
        GetTransactionTool::class,
        ListTransactionsTool::class,
        ReconcileTransactionTool::class,
        RefundTransactionTool::class,
        CancelTransactionTool::class,
    ];
}
