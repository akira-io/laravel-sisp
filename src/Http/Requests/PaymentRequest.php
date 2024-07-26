<?php

namespace Akira\Sisp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function rules(): array
    {

        return [
            'amount' => 'required|numeric',
        ];
    }

    public function htmlForm(): array
    {
        return $this->only('amount');
    }

    public function authorize(): bool
    {

        return true;
    }

    public function getAmount(): float
    {
        return (float) $this->get('amount');
    }
}
