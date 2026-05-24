<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Requests;

use Akira\Sisp\Actions\CanRetryPaymentAction;
use Akira\Sisp\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Validator;

final class RetryPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return URL::hasValidSignature($this);
    }

    public function rules(): array
    {
        return [
            'transaction' => ['required', 'integer', 'exists:sisp_transactions,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('transaction')) {
                return;
            }

            $transaction = Transaction::query()->find($this->integer('transaction'));

            if (! $transaction || ! resolve(CanRetryPaymentAction::class)->handle($transaction)) {
                $validator->errors()->add(
                    'transaction',
                    __('sisp::messages.payment.response.retry_not_available')
                );
            }
        });
    }
}
