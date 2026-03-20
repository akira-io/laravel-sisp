<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Requests;

use Akira\Sisp\Actions\CanRetryPaymentAction;
use Akira\Sisp\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('transaction_id')) {
                return;
            }

            $transaction = Transaction::query()->find($this->integer('transaction_id'));

            if (! $transaction || ! resolve(CanRetryPaymentAction::class)->handle($transaction)) {
                $validator->errors()->add(
                    'transaction_id',
                    __('sisp::messages.payment.response.retry_not_available')
                );
            }
        });
    }
}
