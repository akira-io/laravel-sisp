<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Requests;

use Akira\Sisp\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

final class RetryPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! URL::hasValidSignature($this)) {
            return false;
        }

        $transaction = Transaction::query()->find($this->integer('transaction_id'));

        if (! $transaction) {
            return false;
        }

        $user = $this->user();

        if ($user === null) {
            return true;
        }

        $customerEmail = $transaction->customer_email;
        $userEmail = data_get($user, 'email');

        if ($customerEmail === '' || ! is_string($userEmail) || $userEmail === '') {
            return false;
        }

        return Str::lower($customerEmail) === Str::lower($userEmail);
    }

    public function rules(): array
    {
        return [
            'transaction_id' => ['required', 'integer'],
        ];
    }
}
