<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RetryPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_id' => ['required', 'integer', 'exists:sisp_transactions,id'],
        ];
    }
}