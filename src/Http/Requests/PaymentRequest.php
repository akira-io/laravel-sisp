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
            'transactionId' => ['required', 'string'],
            'options' => ['nullable', 'array'],
            //            'purchaseRequest' => ['required', 'array'],
            //            'purchaseRequest.*.billAddrCountry' => ['required', 'numeric', 'min:3', 'max:3'],
            //            'purchaseRequest.*.billAddrCity' => ['required', 'string', 'min:3', 'max:50'],
            //            'purchaseRequest.*.billAddrLine1' => ['required', 'string', 'min:3', 'max:50'],
            //            'purchaseRequest.*.email' => ['required', 'email'],
            //            'purchaseRequest.*.billAddrPostCode' => ['required', 'string', 'min:3', 'max:16'],
            //
            //            'purchaseRequest.*.actID' => ['nullable','max:64'],
            //            'purchaseRequest.*.acctInfo' => ['nullable','array'],
            //            'purchaseRequest.*.acctInfo.*.chAccAgeInd' => ['nullable', Rule::enum(ChangeAccountAge::class)],
            //            'purchaseRequest.*.acctInfo.*.chAccChange' => ['nullable', 'date_format:Ymd'],
            //            'purchaseRequest.*.acctInfo.*.chAccDate' => ['nullable', 'date_format:Ymd'],
            //            'purchaseRequest.*.acctInfo.*.chAccPwChange' => ['nullable', 'date_format:Ymd'],
            //            'purchaseRequest.*.acctInfo.*.chAccPwChangeInd' => ['nullable', Rule::enum(ChangeAccountAge::class)],
            //            'purchaseRequest.*.acctInfo.*.suspiciousAccActivity' => ['nullable', Rule::enum(SuspiciousAccountActivity::class)],
            //            'purchaseRequest.*.addrMatch' => ['nullable', Rule::enum(AddressMatch::class)],
            //            'purchaseRequest.*.billAddrLine2' => ['nullable', 'string', 'min:3', 'max:50'],
            //            'purchaseRequest.*.billAddrLine3' => ['nullable', 'string', 'min:3', 'max:50'],
            //            'purchaseRequest.*.billAddrState' => ['nullable', 'string', 'min:3', 'max:3'],
            //            'purchaseRequest.*.shipAddrCity' => ['nullable', 'string', 'min:3', 'max:50'],
            //            'purchaseRequest.*.shipAddrState' => ['nullable', 'numeric', 'min:3', 'max:3'],
            //            'purchaseRequest.*.shipAddrCountry' => ['nullable', 'numeric', 'min:3', 'max:3'],
            //            'purchaseRequest.*.shipAddrLine1' => ['nullable', 'string', 'min:3', 'max:50'],
            //            'purchaseRequest.*.shipAddrPostCode' => ['nullable', 'string', 'min:3', 'max:16'],
            //            'purchaseRequest.*.workPhone' => ['nullable', 'array'],
            //            'purchaseRequest.*.workPhone.*.cc' => ['nullable', 'numeric', 'min:1', 'max:3'],
            //            'purchaseRequest.*.workPhone.*.subscriber' => ['nullable', 'numeric', 'min:1', 'max:15'],
            //            'purchaseRequest.*.mobilePhone' => ['nullable', 'array'],
            //            'purchaseRequest.*.mobilePhone.*.cc' => ['nullable', 'numeric', 'min:1', 'max:3'],
            //            'purchaseRequest.*.mobilePhone.*.subscriber' => ['nullable', 'numeric', 'min:1', 'max:15'],

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
