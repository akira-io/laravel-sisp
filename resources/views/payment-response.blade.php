@extends('layouts.app')

@section('content')
<div class="sisp-payment-response-container">
    @if($transaction->status === 'completed')
        <div class="alert alert-success">
            <h1>Pagamento Realizado com Sucesso!</h1>
            <p>Sua transação foi processada com sucesso.</p>
            <p>
                <strong>ID da Transação:</strong> {{ $transaction->id }}<br>
                <strong>Referência do Comerciante:</strong> {{ $transaction->merchant_ref }}<br>
                <strong>Valor:</strong> {{ $transaction->amount }} {{ $transaction->currency }}
            </p>
        </div>
    @elseif($transaction->status === 'failed')
        <div class="alert alert-danger">
            <h1>Pagamento Recusado</h1>
            <p>Desculpe, seu pagamento não foi processado.</p>
            <p>
                <strong>ID da Transação:</strong> {{ $transaction->id }}<br>
                <strong>Motivo:</strong> {{ $transaction->message_type }}
            </p>
        </div>
    @else
        <div class="alert alert-warning">
            <h1>Pagamento Pendente</h1>
            <p>Seu pagamento está sendo processado.</p>
            <p>
                <strong>ID da Transação:</strong> {{ $transaction->id }}<br>
                <strong>Status:</strong> {{ ucfirst($transaction->status) }}
            </p>
        </div>
    @endif

    <div class="mt-4">
        <a href="/" class="btn btn-primary">Voltar ao Início</a>
    </div>
</div>
@endsection