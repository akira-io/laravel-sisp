<?php

namespace Akira\Sisp\Http\Requests;

use Akira\Sisp\Actions\Fields\PaymentFields;
use Akira\Sisp\Actions\PaymentRequestUrl;
use Akira\Sisp\DTOs\PaymentRequestParams;
use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function rules(): array
    {

        return [
            'amount' => ['required','numeric']
        ];
    }
    
    public function authorize(): bool
    {

        return true;
    }

    public function getAmount(): float
    {
        return (float) $this->get('amount');
    }
    
    
    public function payment(): array
    {
        
        $fields =  PaymentFields::make()->withAmount($this->getAmount());
    
        $url = PaymentRequestUrl::make(PaymentRequestParams::make($fields))->url();
        
        return [$fields->toArray(), $url];
    }
    
    
}
