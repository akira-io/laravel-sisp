<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Description('Guided walkthrough for integrating laravel-sisp into a Laravel application end to end.')]
final class IntegrateSispPrompt extends Prompt
{
    public function handle(Request $request): Response
    {
        $stack = (string) ($request->get('stack') ?? 'blade');

        $message = <<<MARKDOWN
            Integrate the akira/laravel-sisp payment gateway into this Laravel application for a {$stack} frontend. Work through these steps and use the sisp-dev tools/resources to ground every detail:

            1. Install: `composer require akira/laravel-sisp`, then run `php artisan sisp:install` to publish config, migrations, and the {$stack} components. Run the migrations.
            2. Configure: call the `config-reference-tool` to review every sisp config key. Then call the `env-scaffold-tool` for the target environment and add the variables to .env.
            3. Callback route: ensure SISP_URL_MERCHANT_RESPONSE points at the package /sisp/callback route and is publicly reachable over HTTPS.
            4. Start payments: post the checkout to the package `sisp.payment` route, which records the transaction and renders the signed form. Use the sisp-ops `build-payment-request-tool` only to preview the fields; its output has no fingerprint and cannot be posted.
            5. Test without live credentials: enable sandbox mode, start a payment, then call the dev `simulate-sandbox-callback-tool` with that transaction and POST the result to /sisp/callback.
            6. Handle results: read docs 04-payment-flow and 05-transaction-management (via `get-doc-tool`) to wire the PaymentCompleted/PaymentFailed events.

            When a payment is refused, read the transaction's `error_message`: it is the refusal reason SISP sends for the customer. Confirm the final wiring against the docs index resource (sisp://docs).
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
                name: 'stack',
                description: 'Frontend stack to target: blade, inertia-react, or inertia-vue.',
                required: false,
            ),
        ];
    }
}
