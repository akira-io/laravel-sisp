@extends('layouts.app')

@section('content')
<div class="relative flex min-h-screen flex-col items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

        {{-- Header with Icon --}}
        <div class="mb-8 text-center fade-in-up" style="animation-delay:.1s">
            <div class="mb-6 flex justify-center">
                <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-purple-600 shadow-xl shadow-purple-500/20 dark:bg-purple-500">
                    <svg class="h-10 w-10 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
                        <path d="M22 11.429V18a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-1.5m17-5.071V10a2 2 0 0 0-2-2h-1m3 3.429h-3"/>
                        <path d="M19 8v6.5a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2zm0 0H5.5"/>
                    </svg>
                </div>
            </div>
            <h1 class="mb-3 text-3xl font-black tracking-tight text-zinc-900 md:text-4xl dark:text-white">{{ __('sisp::payment.redirect_title') }}</h1>
            <p class="text-zinc-600 dark:text-zinc-400">{{ __('sisp::payment.redirect_description') }}</p>
        </div>

        {{-- Security Info Card --}}
        <div class="mb-6 overflow-hidden rounded-2xl border border-green-200 bg-green-50/80 shadow-lg backdrop-blur-sm dark:border-green-800 dark:bg-green-950/50 fade-in-up" style="animation-delay:.2s">
            <div class="p-6">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-600 shadow-lg shadow-green-500/20">
                        <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <span class="text-lg font-bold text-green-700 dark:text-green-400">{{ __('sisp::payment.secure_transaction') }}</span>
                </div>
                <div class="space-y-3">
                    @foreach([
                        ['icon' => 'shield-check', 'text' => __('sisp::payment.official_portal')],
                        ['icon' => 'lock',         'text' => __('sisp::payment.ssl_encryption')],
                        ['icon' => 'shield',       'text' => __('sisp::payment.data_protected')],
                    ] as $i => $feature)
                    <div class="flex items-center gap-3 fade-in-left" style="animation-delay:{{ 0.3 + $i * 0.1 }}s">
                        <svg class="h-4 w-4 text-green-600 dark:text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            @if($feature['icon'] === 'shield-check')
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                            @elseif($feature['icon'] === 'lock')
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                            @endif
                        </svg>
                        <span class="text-sm text-green-700 dark:text-green-300">{{ $feature['text'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Countdown / Status Card --}}
        <div id="countdown-card" class="overflow-hidden rounded-2xl border border-purple-200 bg-purple-50/80 shadow-lg backdrop-blur-sm dark:border-purple-800 dark:bg-purple-950/50 fade-in-up" style="animation-delay:.4s">
            <div class="p-6">
                <div class="flex items-center justify-center gap-3">
                    <svg id="spinner" class="h-6 w-6 animate-spin text-purple-600 dark:text-purple-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span id="countdown-text" class="text-lg font-bold text-purple-700 dark:text-purple-400"></span>
                </div>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                    <div id="progress-bar" class="h-full rounded-full bg-purple-600 transition-all duration-500 dark:bg-purple-500" style="width:0%"></div>
                </div>
            </div>
        </div>

        {{-- Branding --}}
        <div class="mt-8 text-center fade-in" style="animation-delay:.5s">
            <p class="text-xs text-zinc-500 dark:text-zinc-500">{{ __('sisp::payment.developed_by') }}</p>
        </div>
    </div>

    {{-- Hidden form --}}
    <form id="sisp-payment-form" method="POST" action="{{ $formAction }}" class="hidden">
        @foreach($fields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <noscript>
            <button type="submit" class="mt-4 text-purple-600 hover:text-purple-800 underline text-sm">
                {{ __('sisp::payment.manual_redirect_button') }}
            </button>
        </noscript>
    </form>
</div>

<style>
.fade-in-up  { animation: fadeInUp .5s ease both; }
.fade-in-left{ animation: fadeInLeft .5s ease both; }
.fade-in     { animation: fadeIn .5s ease both; }
@keyframes fadeInUp   { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
@keyframes fadeInLeft { from{opacity:0;transform:translateX(-10px)} to{opacity:1;transform:translateX(0)} }
@keyframes fadeIn     { from{opacity:0} to{opacity:1} }
</style>

<script>
(function () {
    var redirectingIn = @json(trans('sisp::payment.redirecting_in'));
    var connectingText = @json(__('sisp::payment.connecting'));
    var totalSeconds = 2;
    var remaining = totalSeconds;

    var card        = document.getElementById('countdown-card');
    var spinner     = document.getElementById('spinner');
    var countdownEl = document.getElementById('countdown-text');
    var progressBar = document.getElementById('progress-bar');
    var form        = document.getElementById('sisp-payment-form');

    function getCountdownText(count) {
        var parts = redirectingIn.split('|');
        var text  = count === 1 ? parts[0] : (parts[1] || parts[0]);
        return text.replace(':count', count);
    }

    function updateUI() {
        if (remaining > 0) {
            countdownEl.textContent = getCountdownText(remaining);
            progressBar.style.width = (((totalSeconds - remaining) / totalSeconds) * 100) + '%';
        } else {
            card.className = card.className
                .replace('border-purple-200', 'border-green-200')
                .replace('bg-purple-50/80', 'bg-green-50/80')
                .replace('dark:border-purple-800', 'dark:border-green-800')
                .replace('dark:bg-purple-950/50', 'dark:bg-green-950/50');
            spinner.className = spinner.className
                .replace('text-purple-600 dark:text-purple-500', 'text-green-600 dark:text-green-500');
            countdownEl.className = countdownEl.className
                .replace('text-purple-700 dark:text-purple-400', 'text-green-700 dark:text-green-400');
            countdownEl.textContent = connectingText;
            progressBar.className = progressBar.className
                .replace('bg-purple-600 dark:bg-purple-500', 'bg-green-600 dark:bg-green-500');
            progressBar.style.width = '100%';
        }
    }

    updateUI();

    var timer = setInterval(function () {
        remaining--;
        updateUI();
        if (remaining <= 0) {
            clearInterval(timer);
            form.submit();
        }
    }, 1000);
})();
</script>
@endsection
