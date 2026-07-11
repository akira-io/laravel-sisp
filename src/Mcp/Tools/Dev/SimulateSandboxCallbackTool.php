<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Tools\Dev;

use Akira\Sisp\Builders\PaymentBuilder;
use Akira\Sisp\Facades\Sisp;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Throwable;

#[IsReadOnly]
#[Description('Build a sandbox SISP callback payload for a given payment shape so a callback can be tested locally. Does not persist anything or contact the gateway.')]
final class SimulateSandboxCallbackTool extends Tool
{
    public function handle(Request $request): Response
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'status' => ['nullable', 'in:success,failed'],
        ]);

        $builder = resolve(PaymentBuilder::class)->amount((float) $request->get('amount'));

        foreach (['currency', 'locale', 'customerEmail'] as $field) {
            $value = $request->get($field);

            if ($value !== null && method_exists($builder, $field)) {
                $builder->{$field}((string) $value);
            }
        }

        try {
            $payload = Sisp::generateSandboxPayload($builder->toData(), (string) ($request->get('status') ?? 'success'));
        } catch (Throwable $e) {
            return Response::error('Could not build sandbox payload: '.$e->getMessage());
        }

        return Response::json([
            'status' => $request->get('status') ?? 'success',
            'callback' => $payload->toArray(),
            'note' => 'POST these fields to the /sisp/callback route to exercise the callback pipeline.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'amount' => $schema->number()
                ->description('Payment amount in major currency units, e.g. 1500.00.')
                ->required(),
            'status' => $schema->string()
                ->description('Outcome to simulate.')
                ->enum(['success', 'failed'])
                ->default('success'),
            'currency' => $schema->string()
                ->description('ISO 4217 numeric currency code. Defaults to the configured currency.'),
            'locale' => $schema->string()
                ->description('Locale for the payment, e.g. "pt".'),
            'customerEmail' => $schema->string()
                ->description('Customer email to embed in the payload.'),
        ];
    }
}
