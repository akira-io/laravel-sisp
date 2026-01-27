<x-sisp::layouts.app>
    <div class="min-h-dvh flex flex-col justify-center items-center bg-gray-900 text-center">
        <x-sisp::loader/>

        <form id="sisp-redirect-form" action="{{ $url }}" method="post" class="mt-8 flex flex-col items-center">
            @csrf
            @foreach ($fields as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            <button type="submit" class="mt-4 text-blue-400 hover:text-blue-300 underline text-sm cursor-pointer transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-blue-500 rounded p-1">
                {{ __('sisp::payment.manual_redirect_button') }}
            </button>

            <noscript>
                <div class="mt-6">
                    <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-900 transition-colors">
                        {{ __('sisp::payment.continue_button') }}
                    </button>
                </div>
            </noscript>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('sisp-redirect-form').submit();
        });
    </script>
</x-sisp::layouts.app>
