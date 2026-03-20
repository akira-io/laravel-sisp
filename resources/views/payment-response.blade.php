@extends('layouts.app')

@section('content')
<div class="relative flex min-h-screen flex-col items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

        {{-- Header with Icon --}}
        <div class="mb-8 text-center fade-in-up" style="animation-delay:.1s">
            <div class="mb-6 flex justify-center">
                @if($transaction->status === 'completed')
                    <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-green-600 to-green-500 shadow-xl shadow-green-500/20">
                        <svg class="h-10 w-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                @elseif($transaction->status === 'failed')
                    <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-red-600 shadow-xl shadow-red-500/20 dark:bg-red-500">
                        <svg class="h-10 w-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                @else
                    <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-yellow-600 to-yellow-500 shadow-xl shadow-yellow-500/20">
                        <svg class="h-10 w-10 animate-pulse text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                @endif
            </div>

            @if($transaction->status === 'completed')
                <h1 class="mb-3 text-3xl font-black tracking-tight md:text-4xl text-green-600 dark:text-green-500">{{ __('sisp::messages.payment.response.success_title') }}</h1>
            @elseif($transaction->status === 'failed')
                <h1 class="mb-3 text-3xl font-black tracking-tight md:text-4xl text-red-600 dark:text-red-500">{{ __('sisp::messages.payment.response.failed_title') }}</h1>
            @else
                <h1 class="mb-3 text-3xl font-black tracking-tight md:text-4xl text-yellow-600 dark:text-yellow-500">{{ __('sisp::messages.payment.response.pending_title') }}</h1>
            @endif

            <p class="text-zinc-600 dark:text-zinc-400">
                @if($transaction->status === 'completed')
                    {{ __('sisp::messages.payment.response.success_message') }}
                @elseif($transaction->status === 'failed')
                    {{ __('sisp::messages.payment.response.failed_message') }}
                @else
                    {{ __('sisp::messages.payment.response.pending_message') }}
                @endif
            </p>
        </div>

        {{-- Transaction Details Card --}}
        @php
            $cardBorder = match($transaction->status) {
                'completed' => 'border-green-200 dark:border-green-800',
                'failed'    => 'border-red-200 dark:border-red-800',
                default     => 'border-yellow-200 dark:border-yellow-800',
            };
            $cardBg = match($transaction->status) {
                'completed' => 'bg-green-50 dark:bg-green-950/50',
                'failed'    => 'bg-red-50 dark:bg-red-950/50',
                default     => 'bg-yellow-50 dark:bg-yellow-950/50',
            };
            $badgeClass = match($transaction->status) {
                'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400',
                'failed'    => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400',
                default     => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-400',
            };
        @endphp
        <div class="mb-6 overflow-hidden rounded-2xl border {{ $cardBorder }} {{ $cardBg }} shadow-lg backdrop-blur-sm fade-in-up" style="animation-delay:.2s">
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('sisp::messages.payment.response.reference') }}</span>
                        <span class="font-mono font-bold text-zinc-900 dark:text-white">
                            {{ $transaction->merchant_ref ?? __('sisp::messages.payment.response.declined') }}
                        </span>
                    </div>

                    @if(in_array($transaction->status, ['completed', 'pending']))
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('sisp::messages.payment.response.amount') }}</span>
                            <span class="text-lg font-bold text-zinc-900 dark:text-white">{{ $transaction->formatted_amount }}</span>
                        </div>
                    @endif

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('sisp::messages.payment.response.status') }}</span>
                        <span class="rounded-full px-3 py-1 text-sm font-bold {{ $badgeClass }}">
                            @if($transaction->status === 'completed')
                                {{ __('sisp::messages.payment.response.success_status') }}
                            @elseif($transaction->status === 'failed')
                                {{ __('sisp::messages.payment.response.failed_status') }}
                            @else
                                {{ __('sisp::messages.payment.response.pending_status') }}
                            @endif
                        </span>
                    </div>

                    @if($transaction->status === 'failed' && $error)
                        <div class="my-4 border-t border-zinc-200 dark:border-zinc-700"></div>
                        <div class="space-y-3">
                            <div>
                                <span class="text-xs font-medium tracking-wider text-zinc-500 uppercase">{{ __('sisp::messages.payment.response.category') }}</span>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $error['categoryLabel'] }}</p>
                            </div>
                            <div>
                                <span class="text-xs font-medium tracking-wider text-zinc-500 uppercase">{{ __('sisp::messages.payment.response.reason') }}</span>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $error['label'] }}</p>
                            </div>
                            <div>
                                <span class="text-xs font-medium tracking-wider text-zinc-500 uppercase">{{ __('sisp::messages.payment.response.action') }}</span>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $error['actionLabel'] }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Invoice Download --}}
        @if($transaction->status === 'completed' && isset($invoice) && $invoice?->pdf_url)
            <div class="mb-4 overflow-hidden rounded-2xl border border-purple-200 bg-purple-50/80 shadow-lg backdrop-blur-sm dark:border-purple-800 dark:bg-purple-950/50 fade-in-up" style="animation-delay:.3s">
                <div class="p-4">
                    <div class="flex gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-600 shadow-lg shadow-purple-500/20">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-purple-800 dark:text-purple-300">{{ __('sisp::messages.payment.response.download_invoice_alert_title') }}</h3>
                            <p class="mt-1 text-sm text-purple-700 dark:text-purple-400">{{ __('sisp::messages.payment.response.download_invoice_alert_message') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <a href="{{ $invoice->pdf_url }}" target="_blank" download="{{ $invoice->invoice_number }}.pdf"
               class="mb-4 flex h-14 w-full items-center justify-center gap-2 rounded-2xl bg-purple-600 hover:bg-purple-700 font-bold text-white shadow-xl shadow-purple-500/20 transition-all hover:scale-[1.02] active:scale-[0.98] fade-in-up"
               style="animation-delay:.35s">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                {{ __('sisp::messages.payment.response.invoice_download') }}
            </a>
        @endif

        {{-- Pending Note --}}
        @if($transaction->status === 'pending')
            <p class="mb-6 text-center text-sm text-zinc-500 dark:text-zinc-400 fade-in" style="animation-delay:.3s">
                {{ __('sisp::messages.payment.response.pending_note') }}
            </p>
        @endif

        {{-- Action Buttons --}}
        <div class="space-y-3 fade-in-up" style="animation-delay:.4s">
            @if($transaction->status === 'failed' && $allowRetry)
                <form method="POST" action="{{ route('sisp.retry-payment') }}">
                    @csrf
                    <input type="hidden" name="transaction_id" value="{{ $transaction->id }}">
                    <button type="submit" class="flex h-14 w-full items-center justify-center gap-2 rounded-2xl bg-purple-600 hover:bg-purple-700 text-lg font-bold text-white shadow-xl shadow-purple-500/20 transition-all hover:scale-[1.02] active:scale-[0.98]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        {{ __('sisp::messages.payment.response.retry_payment') }}
                    </button>
                </form>
            @endif

            @if(in_array($transaction->status, ['completed', 'pending']))
                <button type="button" onclick="document.getElementById('leave-dialog').classList.remove('hidden')"
                    class="flex h-14 w-full items-center justify-center gap-2 rounded-2xl text-lg font-bold transition-all hover:scale-[1.02] active:scale-[0.98]
                    {{ $transaction->status === 'pending'
                        ? 'bg-yellow-500 hover:bg-yellow-600 text-white shadow-xl shadow-yellow-500/20'
                        : 'border-2 border-zinc-200 bg-white text-zinc-700 shadow-lg hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:border-zinc-600 dark:hover:bg-zinc-700' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    {{ __('sisp::messages.payment.response.back_home') }}
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            @endif

            @if($transaction->status === 'failed')
                <a href="/" class="flex h-14 w-full items-center justify-center gap-2 rounded-2xl border-2 border-zinc-200 bg-white font-bold text-zinc-700 shadow-lg transition-all hover:scale-[1.02] hover:border-zinc-300 hover:bg-zinc-50 active:scale-[0.98] dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:border-zinc-600 dark:hover:bg-zinc-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    {{ __('sisp::messages.payment.response.cancel_payment') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Leave Confirmation Dialog --}}
    <div id="leave-dialog" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="relative mx-4 w-full max-w-sm overflow-hidden rounded-3xl bg-zinc-900 shadow-2xl">
            <button onclick="document.getElementById('leave-dialog').classList.add('hidden')"
                class="absolute top-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-zinc-700 text-zinc-300 hover:bg-zinc-600 transition-colors">
                ✕
            </button>
            <div class="px-6 pt-8 pb-6 text-center">
                <div class="mb-5 flex justify-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-purple-600 shadow-lg shadow-purple-500/30">
                        <svg class="h-8 w-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>
                <h3 class="mb-3 text-2xl font-black text-white">{{ __('sisp::messages.payment.response.leave_confirmation_title') }}</h3>
                <p class="text-sm leading-relaxed text-zinc-400">{{ __('sisp::messages.payment.response.leave_confirmation_message') }}</p>
            </div>
            <div class="border-t border-zinc-700"></div>
            <div class="flex items-center gap-3 p-4">
                <button onclick="document.getElementById('leave-dialog').classList.add('hidden')"
                    class="flex-1 py-3 text-sm font-semibold text-zinc-400 hover:text-zinc-200 transition-colors">
                    {{ __('sisp::messages.payment.response.stay_on_page') }}
                </button>
                <a href="/" class="flex-1 rounded-full bg-purple-600 py-3 text-center text-sm font-bold text-white hover:bg-purple-500 transition-all">
                    {{ __('sisp::messages.payment.response.leave_page') }}
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.fade-in-up  { animation: fadeInUp .5s ease both; }
.fade-in     { animation: fadeIn .5s ease both; }
@keyframes fadeInUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
@keyframes fadeIn   { from{opacity:0} to{opacity:1} }
</style>

<script>
(function () {
    @if(!($transaction->status === 'failed'))
    var beforeUnloadHandler = function(e) {
        e.preventDefault();
        e.returnValue = '';
    };
    window.addEventListener('beforeunload', beforeUnloadHandler);

    window.history.pushState(null, '', window.location.href);
    window.addEventListener('popstate', function() {
        window.history.pushState(null, '', window.location.href);
        document.getElementById('leave-dialog').classList.remove('hidden');
    });
    @endif
})();
</script>
@endsection
