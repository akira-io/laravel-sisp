<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\TransactionLogContext;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Support\Facades\Crypt;
use Throwable;

final readonly class LogTransactionChangesAction
{
    public function handle(Transaction $transaction): void
    {
        $changes = $this->changes($transaction);

        if ($changes['changed_attributes'] === []) {
            return;
        }

        $transaction->logs()->create([
            'source' => TransactionLogContext::current(),
            'changed_attributes' => $changes['changed_attributes'],
            'old_values' => $changes['old_values'],
            'new_values' => $changes['new_values'],
        ]);
    }

    private function changes(Transaction $transaction): array
    {
        $changedAttributes = [];
        $oldValues = [];
        $newValues = [];

        foreach (array_keys($transaction->getChanges()) as $attribute) {
            if ($attribute === 'updated_at') {
                continue;
            }

            $oldValue = $this->normalize($transaction->getOriginal($attribute));
            $newValue = $this->normalize($transaction->getAttribute($attribute));

            if ($oldValue === $newValue) {
                continue;
            }

            $changedAttributes[] = $attribute;
            $oldValues[$attribute] = $oldValue;
            $newValues[$attribute] = $newValue;
        }

        return [
            'changed_attributes' => $changedAttributes,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ];
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if (is_string($value)) {
            return $this->normalizeString($value);
        }

        if (! is_array($value)) {
            return $value;
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            $normalized[$key] = $this->normalize($item);
        }

        return $normalized;
    }

    private function normalizeString(string $value): mixed
    {
        try {
            $decrypted = Crypt::decryptString($value);
        } catch (Throwable) {
            return $value;
        }

        $decoded = json_decode($decrypted, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->normalize($decoded);
        }

        return $decrypted;
    }
}
