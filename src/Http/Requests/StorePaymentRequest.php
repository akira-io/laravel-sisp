<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            //            'currency' => ['sometimes', 'string', 'size:3'],
            //            'merchant_ref' => ['sometimes', 'string', 'max:255'],
            //            'merchant_session' => ['sometimes', 'string', 'max:255'],
            //            'transaction_code' => ['sometimes', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['sometimes', 'string', 'max:255'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.total_price' => ['required', 'numeric', 'min:0'],
            'items.*.description' => ['sometimes', 'string'],
            'items.*.metadata' => ['sometimes', 'array'],
        ];
    }
}
