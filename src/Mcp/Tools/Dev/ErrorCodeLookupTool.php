<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Tools\Dev;

use Akira\Sisp\Enums\ErrorMessageType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Resolve a SISP error/response code to its label, category, and recommended remediation action.')]
final class ErrorCodeLookupTool extends Tool
{
    public function handle(Request $request): Response
    {
        $code = mb_trim((string) $request->get('code'));
        $error = ErrorMessageType::tryFrom($code);

        if (! $error instanceof ErrorMessageType) {
            return Response::error("Unknown SISP error code \"{$code}\". Codes range from 1 to 99; 99 is the generic error.");
        }

        return Response::json([
            'code' => $error->value,
            'name' => $error->name,
            'label' => $error->label(),
            'category' => $error->category(),
            'category_label' => $error->categoryLabel(),
            'action' => $error->action(),
            'action_label' => $error->actionLabel(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()
                ->description('The numeric SISP response/error code, e.g. "51" for insufficient funds.')
                ->required(),
        ];
    }
}
