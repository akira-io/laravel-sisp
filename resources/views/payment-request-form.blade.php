<x-sisp::layouts.app>
    <div class="min-h-screen flex flex-col justify-center items-center bg-gray-900 text-center p-4">
        <x-sisp::loader/>

        <p class="mt-4 text-white text-lg font-medium animate-pulse">
            {{ __('sisp::payment.redirect_title') }}...
        </p>

        <form id="sisp-request-form" action="{{ $url }}" method="post" class="mt-4 flex flex-col items-center">
            @csrf
            @foreach ($fields as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            <button type="submit" class="mt-2 text-gray-400 hover:text-white underline text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-blue-500 rounded">
                {{ __('sisp::payment.manual_redirect_button') }}
            </button>

            <noscript>
                <div class="mt-6">
                     <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-900 transition-colors">
                        {{ __('sisp::payment.continue_button') }}
                    </button>
                </div>
            </noscript>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('sisp-request-form').submit();
        });
    </script>
</x-sisp::layouts.app>
