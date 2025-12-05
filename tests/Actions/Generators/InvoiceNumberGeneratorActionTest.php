<?php

declare(strict_types=1);

use Akira\Sisp\Actions\Generators\InvoiceNumberGeneratorAction;
use Akira\Sisp\Models\Transaction;

beforeEach(function (): void {
    // Sensible defaults
    config()->set('sisp.invoice.number_prefix', 'INV');
});

it('generates sequential invoice number with prefix', function (): void {
    config()->set('sisp.invoice.number_format', 'sequential');
    $t = Transaction::factory()->create();

    $num = resolve(InvoiceNumberGeneratorAction::class)->handle($t);
    expect($num)->toBe('INV'.mb_str_pad((string) $t->id, 6, '0', STR_PAD_LEFT));
});

it('generates date-based invoice number', function (): void {
    config()->set('sisp.invoice.number_format', 'date-based');
    $t = Transaction::factory()->create();

    $expected = sprintf('INV-%s%s-%s', $t->created_at->format('Y'), $t->created_at->format('m'), mb_str_pad((string) $t->id, 6, '0', STR_PAD_LEFT));
    $num = resolve(InvoiceNumberGeneratorAction::class)->handle($t);
    expect($num)->toBe($expected);
});

it('falls back to default which is date-based', function (): void {
    config()->set('sisp.invoice.number_format', 'unknown');
    $t = Transaction::factory()->create();
    $num = resolve(InvoiceNumberGeneratorAction::class)->handle($t);
    expect($num)->toContain($t->created_at->format('Ym'));
});
