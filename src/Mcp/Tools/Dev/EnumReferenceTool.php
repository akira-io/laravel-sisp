<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Tools\Dev;

use Akira\Sisp\Enums\ErrorMessageType;
use Akira\Sisp\Enums\InvoiceStatus;
use Akira\Sisp\Enums\SuccessMessageType;
use Akira\Sisp\Enums\TransactionCode;
use Akira\Sisp\Enums\TransactionStatus;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('List the cases of a laravel-sisp enum with their values and labels.')]
final class EnumReferenceTool extends Tool
{
    private const array ENUMS = [
        'transaction_status' => TransactionStatus::class,
        'transaction_code' => TransactionCode::class,
        'error_message' => ErrorMessageType::class,
        'invoice_status' => InvoiceStatus::class,
        'success_message' => SuccessMessageType::class,
    ];

    public function handle(Request $request): Response
    {
        $name = (string) $request->get('enum');
        $enumClass = self::ENUMS[$name] ?? null;

        if ($enumClass === null) {
            return Response::error("Unknown enum \"{$name}\". Available: ".implode(', ', array_keys(self::ENUMS)));
        }

        $cases = [];

        foreach ($enumClass::cases() as $case) {
            $entry = ['name' => $case->name, 'value' => $case->value];

            if (method_exists($case, 'label')) {
                $entry['label'] = $case->label();
            }

            if (method_exists($case, 'category')) {
                $entry['category'] = $case->category();
                $entry['action'] = $case->action();
            }

            $cases[] = $entry;
        }

        return Response::json(['enum' => $name, 'cases' => $cases]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'enum' => $schema->string()
                ->description('Which enum to describe.')
                ->enum(array_keys(self::ENUMS))
                ->required(),
        ];
    }
}
