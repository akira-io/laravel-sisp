<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Tools\Dev;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Explain the config/sisp.php keys: their purpose and current value. Secrets are redacted.')]
final class ConfigReferenceTool extends Tool
{
    private const array DESCRIPTIONS = [
        'url' => 'SISP gateway endpoint the payment form posts to.',
        'driver' => 'Active driver: null (auto), "production", or "sandbox".',
        'pipelines' => 'Ordered payment and callback pipe classes. Reorder or extend to customise the flow.',
        'generators' => 'Classes that generate the merchant reference, session, and timestamp.',
        'posID' => 'Merchant POS identifier issued by SISP.',
        'posAutCode' => 'Merchant POS authorisation code used to sign requests (secret).',
        'currency' => 'ISO 4217 numeric currency code. Default 132 (CVE).',
        'language_messages' => 'Gateway message language: EN or PT.',
        'fingerprint_version' => 'SISP fingerprint algorithm version.',
        'url_merchant_response' => 'Callback URL SISP posts the payment result to.',
        'is_3dsec' => '3D Secure indicator (0 or 1). When 1, customer data is required.',
        'transaction_code' => 'Default SISP transaction code. 1 = purchase.',
        'merchantId' => 'Merchant identifier.',
        'tables' => 'Database table name overrides.',
        'idempotency' => 'Idempotency toggle and the request field names treated as idempotency keys.',
        'identifier_generation' => 'Retry budget and backoff when generating unique identifiers.',
        'redirect_url' => 'Where to send the customer after the payment result is rendered.',
        'sandbox' => 'Force sandbox mode regardless of driver.',
        'transaction_status' => 'POS status API URL, credentials, and reconciliation thresholds.',
        'use_blade' => 'Render the payment form and result with Blade views.',
        'use_inertia' => 'Render the payment form and result with Inertia components.',
        'invoice' => 'Invoice numbering, storage disk/path, template, and company details.',
        'allow_retry' => 'Allow failed payments to be retried.',
        'rate_limiting' => 'Per-IP, per-merchant, and per-user rate limits.',
        'security' => 'Metadata collection, VPN/proxy detection, risk scoring, and spend caps.',
        'geolocation' => 'Geolocation provider and API keys for request metadata.',
        'middleware' => 'Route middleware for the payment, retry, and refund endpoints.',
        'mcp' => 'MCP server toggles for the local and web transports.',
    ];

    private const array SECRET_KEYS = ['posAutCode', 'transaction_status'];

    public function handle(Request $request): Response
    {
        $key = $request->get('key');

        if ($key !== null) {
            $key = (string) $key;

            if (! array_key_exists($key, self::DESCRIPTIONS)) {
                return Response::error("Unknown config key \"{$key}\". Known keys: ".implode(', ', array_keys(self::DESCRIPTIONS)));
            }

            return Response::json([
                'key' => "sisp.{$key}",
                'description' => self::DESCRIPTIONS[$key],
                'value' => $this->valueFor($key),
            ]);
        }

        $reference = [];

        foreach (self::DESCRIPTIONS as $name => $description) {
            $reference[$name] = [
                'key' => "sisp.{$name}",
                'description' => $description,
                'value' => $this->valueFor($name),
            ];
        }

        return Response::json($reference);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'key' => $schema->string()
                ->description('A single top-level sisp config key to explain. Omit to list every key.')
                ->enum(array_keys(self::DESCRIPTIONS)),
        ];
    }

    private function valueFor(string $key): mixed
    {
        if (in_array($key, self::SECRET_KEYS, true)) {
            return '[redacted]';
        }

        return config("sisp.{$key}");
    }
}
