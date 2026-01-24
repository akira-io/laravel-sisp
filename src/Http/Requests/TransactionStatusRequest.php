<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TransactionStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'posID' => ['required', 'integer'],
            'posAuthCode' => ['required', 'string'],
            'merchantRef' => ['required', 'string'],
        ];
    }
}
