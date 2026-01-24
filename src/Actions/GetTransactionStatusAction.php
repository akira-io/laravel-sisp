<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

final class GetTransactionStatusAction
{
    public function handle(array $data): array
    {
        $baseUrl = $this->getBaseUrl();
        $endpoint = $baseUrl.'/pos/transaction-status';

        $credentials = base64_encode($data['posID'].':'.$data['posAuthCode']);

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $credentials,
        ])->post($endpoint, [
            'posID' => $data['posID'],
            'posAuthCode' => $data['posAuthCode'],
            'merchantRef' => $data['merchantRef'],
        ]);

        return $response->json();
    }

    private function getBaseUrl(): string
    {
        if (Config::get('sisp.sandbox')) {
            return 'https://comerciante.teste.sisp.cv';
        }

        return Config::get('sisp.url', 'https://comerciante.vinti4.cv');
    }
}
