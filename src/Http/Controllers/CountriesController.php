<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Support\Countries;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

final readonly class CountriesController
{
    public function index(): JsonResponse
    {
        $countries = Cache::rememberForever('sisp.countries', fn (): array => Countries::all());

        return response()->json($countries);
    }
}
