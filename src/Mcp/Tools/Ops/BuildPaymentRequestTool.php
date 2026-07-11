<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Tools\Ops;

use Akira\Sisp\Builders\PaymentBuilder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Throwable;

#[IsReadOnly]
#[Description('Build a signed SISP payment request payload from the given inputs. Returns the form fields to post to the gateway; it does not persist a transaction or charge anything.')]
final class BuildPaymentRequestTool extends Tool
{
    private const array STRING_FIELDS = [
        'currency', 'transactionCode', 'token', 'entityCode', 'referenceNumber',
        'locale', 'customerEmail', 'customerCountry', 'customerCity',
        'customerAddress', 'customerPostalCode', 'customerPhone',
        'merchantRef', 'merchantSession',
    ];

    public function handle(Request $request): Response
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $builder = resolve(PaymentBuilder::class)->amount((float) $request->get('amount'));

        foreach (self::STRING_FIELDS as $field) {
            $value = $request->get($field);

            if ($value !== null && $value !== '') {
                $builder->{$field}((string) $value);
            }
        }

        try {
            $payload = $builder->build();
        } catch (Throwable $e) {
            return Response::error('Could not build payment request: '.$e->getMessage());
        }

        return Response::json([
            'payment_request' => $payload->toArray(),
            'note' => 'Post these fields as a form to the SISP gateway URL to start the hosted payment.',
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
            'currency' => $schema->string()->description('ISO 4217 numeric code. Defaults to the configured currency.'),
            'transactionCode' => $schema->string()->description('SISP transaction code. Defaults to the configured code (1 = purchase).'),
            'locale' => $schema->string()->description('Locale for the payment form, e.g. "pt".'),
            'customerEmail' => $schema->string()->description('Customer email. Required when 3D Secure is enabled.'),
            'customerCountry' => $schema->string()->description('Customer country (ISO alpha-2). Required when 3D Secure is enabled.'),
            'customerCity' => $schema->string()->description('Customer city. Required when 3D Secure is enabled.'),
            'customerAddress' => $schema->string()->description('Customer address. Required when 3D Secure is enabled.'),
            'customerPostalCode' => $schema->string()->description('Customer postal code.'),
            'customerPhone' => $schema->string()->description('Customer phone number.'),
            'token' => $schema->string()->description('Stored card token for token payments.'),
            'entityCode' => $schema->string()->description('Entity code for service payments.'),
            'referenceNumber' => $schema->string()->description('Reference number for service payments.'),
            'merchantRef' => $schema->string()->description('Override the generated merchant reference.'),
            'merchantSession' => $schema->string()->description('Override the generated merchant session.'),
        ];
    }
}
