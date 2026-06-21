<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Resources;

use Akira\Sisp\Enums\ErrorMessageType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Uri('sisp://error-codes')]
#[Description('Every SISP response/error code mapped to its label, category, and recommended action.')]
final class ErrorCodeCatalogResource extends Resource
{
    public function handle(Request $request): Response
    {
        $codes = array_map(static fn (ErrorMessageType $error): array => [
            'code' => $error->value,
            'name' => $error->name,
            'label' => $error->label(),
            'category' => $error->category(),
            'action' => $error->action(),
        ], ErrorMessageType::cases());

        return Response::json(['error_codes' => $codes]);
    }
}
