<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Prompts;

use Akira\Sisp\Enums\ErrorMessageType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Description('Explain a failed SISP payment from its error code and propose a remediation path.')]
final class DiagnosePaymentFailurePrompt extends Prompt
{
    public function handle(Request $request): Response
    {
        $code = mb_trim((string) $request->get('code'));
        $error = ErrorMessageType::tryFrom($code);

        if (! $error instanceof ErrorMessageType) {
            return Response::text("Resolve SISP error code \"{$code}\" with the error_code_lookup tool, then explain the cause and the next step for the customer.");
        }

        $message = <<<MARKDOWN
            A SISP payment failed with code {$error->value} ({$error->name}).

            - Meaning: {$error->label()}
            - Category: {$error->category()}
            - Recommended action: {$error->action()}

            Explain to the customer in plain language what went wrong, whether retrying will help, and the concrete next step. If the category is "system" or "issuer", advise retrying or reconciling the transaction with the sisp-ops reconcile tool. If it is "funds" or "card", advise using a different card or contacting the issuer.
            MARKDOWN;

        return Response::text($message);
    }

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'code',
                description: 'The SISP response/error code returned with the failed payment.',
                required: true,
            ),
        ];
    }
}
