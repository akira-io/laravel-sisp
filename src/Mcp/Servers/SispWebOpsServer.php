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
use Laravel\Mcp\Server\Contracts\Transport;
use Override;

#[Name('sisp-ops')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
    Runtime operations for the akira/laravel-sisp payment gateway, exposed over an
    authenticated web transport.

    Build payment request payloads, query and reconcile transaction status, and
    list or inspect stored transactions. Refund and cancel are destructive and are
    only available when the host application opts in via sisp.mcp.web.expose_destructive.
    MARKDOWN)]
final class SispWebOpsServer extends Server
{
    #[Override]
    protected array $tools = [
        BuildPaymentRequestTool::class,
        QueryTransactionStatusTool::class,
        GetTransactionTool::class,
        ListTransactionsTool::class,
        ReconcileTransactionTool::class,
    ];

    public function __construct(Transport $transport)
    {
        parent::__construct($transport);

        if (config('sisp.mcp.web.expose_destructive', false)) {
            $this->tools = [
                ...$this->tools,
                RefundTransactionTool::class,
                CancelTransactionTool::class,
            ];
        }
    }
}
