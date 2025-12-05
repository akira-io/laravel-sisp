<?php

declare(strict_types=1);

use Akira\Sisp\Concerns\HasSispTransactions;
use Akira\Sisp\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class OrderWithSisp extends Model
{
    use HasSispTransactions;
    use Illuminate\Database\Eloquent\Factories\HasFactory;

    public $timestamps = false;

    protected $table = 'orders_with_sisp';

    protected $fillable = ['sisp_transaction_id'];
}

it('defines belongsTo relation to Transaction via sisp_transaction_id', function (): void {
    Schema::create('orders_with_sisp', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('sisp_transaction_id')->nullable();
    });

    $transaction = Transaction::factory()->create();
    $order = OrderWithSisp::query()->create(['sisp_transaction_id' => $transaction->id]);

    $related = $order->sispTransaction;
    expect($related)->toBeInstanceOf(Transaction::class)
        ->and($related->id)->toBe($transaction->id);
});
