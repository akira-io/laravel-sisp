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
            2. Configure: call the `config_reference` tool to review every sisp config key. Then call the `env_scaffold` tool for the target environment and add the variables to .env.
            3. Callback route: ensure SISP_URL_MERCHANT_RESPONSE points at the package /sisp/callback route and is publicly reachable over HTTPS.
            4. Build a payment: use the sisp-ops `build_payment_request` tool to produce the form payload, and render it for the customer.
            5. Test without live credentials: enable sandbox mode, then use the dev `simulate_sandbox_callback` tool to generate a callback payload and POST it to /sisp/callback.
            6. Handle results: read docs 04-payment-flow and 05-transaction-management (via `get_doc`) to wire the PaymentCompleted/PaymentFailed events.

            For any SISP error code you encounter, use the `error_code_lookup` tool to get the recommended action. Confirm the final wiring against the docs index resource (sisp://docs).
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
