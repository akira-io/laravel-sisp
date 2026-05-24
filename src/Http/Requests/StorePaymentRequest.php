<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['sometimes', 'string', 'max:255'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.total_price' => ['required', 'numeric', 'min:0'],
            'items.*.description' => ['sometimes', 'string'],
            'items.*.metadata' => ['sometimes', 'array'],
            'customer_name' => ['sometimes', 'string', 'max:255'],
            'customer_email' => ['sometimes', 'email', 'max:255'],
            'customer_phone' => ['sometimes', 'string', 'max:20'],
            'customer_country' => ['sometimes', 'string', 'max:2'],
            'customer_city' => ['sometimes', 'string', 'max:255'],
            'customer_address' => ['sometimes', 'string', 'max:255'],
            'customer_postal_code' => ['sometimes', 'string', 'max:20'],
            'locale' => ['sometimes', 'string', 'max:10'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('amount') || $validator->errors()->has('items')) {
                return;
            }

            $items = $this->input('items', []);
            if (! is_array($items)) {
                return;
            }

            $submittedTotal = 0;

            foreach ($items as $index => $item) {
                if (! is_array($item)) {
                    return;
                }

                $lineTotal = $this->amountInMinorUnits($item['total_price'] ?? 0);
                $expectedLineTotal = ((int) ($item['quantity'] ?? 0)) * $this->amountInMinorUnits($item['unit_price'] ?? 0);
                $submittedTotal += $lineTotal;

                if ($lineTotal !== $expectedLineTotal) {
                    $validator->errors()->add("items.{$index}.total_price", 'Item total must equal quantity multiplied by unit price.');
                }
            }

            if ($this->amountInMinorUnits($this->input('amount')) !== $submittedTotal) {
                $validator->errors()->add('amount', 'Payment amount must equal the sum of item totals.');
            }
        });
    }

    private function amountInMinorUnits(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
