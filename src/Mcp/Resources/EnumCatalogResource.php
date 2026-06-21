<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Resources;

use Akira\Sisp\Enums\ErrorMessageType;
use Akira\Sisp\Enums\InvoiceStatus;
use Akira\Sisp\Enums\SuccessMessageType;
use Akira\Sisp\Enums\TransactionCode;
use Akira\Sisp\Enums\TransactionStatus;
use BackedEnum;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Uri('sisp://enums')]
#[Description('Catalog of every laravel-sisp enum with its cases, values, and labels.')]
final class EnumCatalogResource extends Resource
{
    public function handle(Request $request): Response
    {
        return Response::json([
            'transaction_status' => $this->cases(TransactionStatus::cases()),
            'transaction_code' => $this->cases(TransactionCode::cases()),
            'invoice_status' => $this->cases(InvoiceStatus::cases()),
            'success_message' => $this->cases(SuccessMessageType::cases()),
            'error_message' => $this->cases(ErrorMessageType::cases()),
        ]);
    }

    /**
     * @param  array<int, BackedEnum>  $cases
     * @return array<int, array<string, mixed>>
     */
    private function cases(array $cases): array
    {
        return array_map(static function (BackedEnum $case): array {
            $entry = ['name' => $case->name, 'value' => $case->value];

            if (method_exists($case, 'label')) {
                $entry['label'] = $case->label();
            }

            return $entry;
        }, $cases);
    }
}
