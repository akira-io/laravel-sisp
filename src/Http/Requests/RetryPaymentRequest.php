<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Requests;

use Akira\Sisp\Actions\CanRetryPaymentAction;
use Akira\Sisp\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class RetryPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return URL::hasValidSignature($this);
    }

    /**
     * The signature covers the query string only, so the transaction is read from
     * there: a request body could otherwise name a transaction the link never signed.
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return $this->query->all();
    }

    public function rules(): array
    {
        return [
            'transaction' => ['required', 'integer', Rule::exists(new Transaction()->getTable(), 'id')],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('transaction')) {
                return;
            }

            $transaction = Transaction::query()->find($this->transactionId());

            if (! $transaction || ! resolve(CanRetryPaymentAction::class)->handle($transaction)) {
                $validator->errors()->add(
                    'transaction',
                    __('sisp::messages.payment.response.retry_not_available')
                );
            }
        });
    }

    public function transactionId(): int
    {
        return (int) $this->query('transaction');
    }
}
