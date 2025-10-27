@extends('layouts.app')

@section('content')
<div class="sisp-payment-container">
    <h1>Processando Pagamento...</h1>

    <form id="sisp-payment-form" method="POST" action="{{ $formAction }}">
        @foreach($fields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('sisp-payment-form').submit();
        });
    </script>

    <noscript>
        <p>Por favor, clique no botão abaixo para continuar:</p>
        <button onclick="document.getElementById('sisp-payment-form').submit();">
            Continuar com o Pagamento
        </button>
    </noscript>
</div>
@endsection