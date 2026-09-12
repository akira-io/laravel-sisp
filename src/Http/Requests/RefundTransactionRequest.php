<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Requests;

use Akira\Sisp\Models\Transaction;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class RefundTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $transaction = $this->route('transaction');

        if (! $user || ! $transaction instanceof Transaction) {
            return false;
        }

        return $user->can('refund', $transaction);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function refundAmount(): float
    {
        return (float) $this->validated('amount');
    }

    public function refundReason(): string
    {
        return $this->string('reason', 'user_refund')->toString();
    }

    protected function failedAuthorization(): never
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Unauthorized to refund this transaction.',
        ], 403));
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'The refund request is invalid.',
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }
}
