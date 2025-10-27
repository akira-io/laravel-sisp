<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Requests;

use Akira\Sisp\Exceptions\UnableToDecryptDataException;
use Illuminate\Foundation\Http\FormRequest;

final class PaymentRequest extends FormRequest
{
    /**
     * The attributes that are validated.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric'],
            'transactionId' => ['required', 'string', 'integer'],
            'details' => ['nullable', 'array'],
        ];
    }

    /**
     * Determine if the request is authorized.
     */
    public function authorize(): bool
    {

        return true;
    }

    /**
     * Prepare the data for validation.
     *
     * @throws UnableToDecryptDataException
     */
    //    protected function prepareForValidation(): void
    //    {
    //        $decryptedAmount = Crypto::decrypt(type($this->get('amount'))->asString());
    //        $decryptedTransactionId = Crypto::decrypt(type($this->get('transactionId'))->asString());
    //
    //        if (! $decryptedAmount || ! $decryptedTransactionId) {
    //            throw new UnableToDecryptDataException();
    //        }
    //
    //        $this->merge([
    //            'amount' => $decryptedAmount,
    //            'transactionId' => $decryptedTransactionId,
    //        ]);
    //    }
}
