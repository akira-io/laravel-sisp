<x-sisp::layouts.app>
    <div class="sisp-payment-container flex flex-col items-center justify-center min-h-dvh py-12 text-center">

        <x-sisp::loader class="mb-6" />

        <h1 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">
            {{ __('sisp::payment.redirect_title') }}
        </h1>

        <p class="mb-8 text-gray-600 dark:text-gray-400 max-w-md">
            {{ __('sisp::payment.redirect_description') }}
        </p>

        <form id="sisp-payment-form" method="POST" action="{{ $formAction }}" class="flex flex-col items-center">
            @foreach($fields as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach

            <button type="submit" class="mt-4 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline text-sm cursor-pointer transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded">
                {{ __('sisp::payment.manual_redirect_button') }}
            </button>

            <noscript>
                <div class="mt-4">
                    <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        {{ __('sisp::payment.continue_button') }}
                    </button>
                </div>
            </noscript>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('sisp-payment-form').submit();
            });
        </script>
    </div>
</x-sisp::layouts.app>
