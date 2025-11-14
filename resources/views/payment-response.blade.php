@extends('layouts.app')

@section('content')
<div class="flex min-h-screen items-center justify-center bg-background">
    <div class="w-full max-w-md rounded-lg bg-card p-8 shadow-lg border border-border">
        @if($transaction->status === 'completed')
            <div class="space-y-6">
                <div class="space-y-2 text-center">
                    <div class="flex justify-center">
                        <div class="rounded-full bg-green-100 dark:bg-green-950 p-4">
                            <svg class="h-8 w-8 text-green-600 dark:text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <h2 class="text-2xl font-bold text-green-600 dark:text-green-500">{{ __('sisp::messages.payment.response.success_title') }}</h2>
                    <p class="text-muted-foreground">{{ __('sisp::messages.payment.response.success_message') }}</p>
                </div>

                <div class="space-y-2 rounded-lg bg-green-50 dark:bg-green-950 p-4 text-sm text-foreground border border-green-200 dark:border-green-800">
                    <p><strong>{{ __('sisp::messages.payment.response.reference') }}:</strong> {{ $transaction->merchant_ref }}</p>
                    <p><strong>{{ __('sisp::messages.payment.response.amount') }}:</strong> {{ $transaction->formatted_amount }}</p>
                    <p><strong>{{ __('sisp::messages.payment.response.status') }}:</strong> <span class="font-medium text-green-600 dark:text-green-500">{{ __('sisp::messages.payment.response.success_status') }}</span></p>
                </div>
            </div>
        @elseif($transaction->status === 'failed')
            <div class="space-y-6">
                <div class="space-y-2 text-center">
                    <div class="flex justify-center">
                        <div class="rounded-full bg-red-100 dark:bg-red-950 p-4">
                            <svg class="h-8 w-8 text-red-600 dark:text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <h2 class="text-2xl font-bold text-red-600 dark:text-red-500">{{ __('sisp::messages.payment.response.failed_title') }}</h2>
                    <p class="text-muted-foreground">{{ __('sisp::messages.payment.response.failed_message') }}</p>
                </div>

                <div class="space-y-2 rounded-lg bg-red-50 dark:bg-red-950 p-4 text-sm text-foreground border border-red-200 dark:border-red-800">
                    <p><strong>{{ __('sisp::messages.payment.response.reference') }}:</strong> {{ $transaction->merchant_ref ?? __('sisp::messages.payment.response.declined') }}</p>
                    @if($error)
                        <p><strong>{{ __('sisp::messages.payment.response.category') }}:</strong> {{ $error['categoryLabel'] }}</p>
                        <p><strong>{{ __('sisp::messages.payment.response.reason') }}:</strong> {{ $error['label'] }}</p>
                        <p><strong>{{ __('sisp::messages.payment.response.action') }}:</strong> {{ $error['actionLabel'] }}</p>
                    @endif
                    <p><strong>{{ __('sisp::messages.payment.response.status') }}:</strong> <span class="font-medium text-red-600 dark:text-red-500">{{ __('sisp::messages.payment.response.failed_status') }}</span></p>
                </div>
            </div>
        @else
            <div class="space-y-6">
                <div class="space-y-2 text-center">
                    <div class="flex justify-center">
                        <div class="rounded-full bg-yellow-100 dark:bg-yellow-950 p-4">
                            <svg class="h-8 w-8 animate-spin text-yellow-600 dark:text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <h2 class="text-2xl font-bold text-yellow-600 dark:text-yellow-500">{{ __('sisp::messages.payment.response.pending_title') }}</h2>
                    <p class="text-muted-foreground">{{ __('sisp::messages.payment.response.pending_message') }}</p>
                </div>

                <div class="space-y-2 rounded-lg bg-yellow-50 dark:bg-yellow-950 p-4 text-sm text-foreground border border-yellow-200 dark:border-yellow-800">
                    <p><strong>{{ __('sisp::messages.payment.response.reference') }}:</strong> {{ $transaction->merchant_ref }}</p>
                    <p><strong>{{ __('sisp::messages.payment.response.status') }}:</strong> <span class="font-medium text-yellow-600 dark:text-yellow-500">{{ __('sisp::messages.payment.response.pending_status') }}</span></p>
                </div>

                <p class="text-center text-xs text-muted-foreground">
                    {{ __('sisp::messages.payment.response.pending_note') }}
                </p>
            </div>
        @endif

        <div class="mt-8 space-y-2">
            @if($transaction->status === 'failed' && $allowRetry)
                <form method="POST" action="{{ route('sisp.retry-payment') }}" class="w-full">
                    @csrf
                    <input type="hidden" name="transaction_id" value="{{ $transaction->id }}">
                    <button type="submit" class="block w-full rounded-lg bg-blue-600 hover:bg-blue-700 px-4 py-2 text-center font-medium text-white transition">
                        {{ __('sisp::messages.payment.response.retry_payment') }}
                    </button>
                </form>
            @endif
            <a href="/" class="block w-full rounded-lg @if($transaction->status === 'failed') bg-gray-200 hover:bg-gray-300 text-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-100 @else bg-primary hover:bg-primary/90 text-primary-foreground @endif px-4 py-2 text-center font-medium transition">
                @if($transaction->status === 'failed')
                    {{ __('sisp::messages.payment.response.cancel_payment') }}
                @else
                    {{ __('sisp::messages.payment.response.back_home') }}
                @endif
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const handleBeforeUnload = (e) => {
            e.preventDefault();
            e.returnValue = '';
        };

        window.addEventListener('beforeunload', handleBeforeUnload);
    });
</script>
@endsection