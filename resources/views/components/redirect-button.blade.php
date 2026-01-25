@props(['name'])

<a href="{{url(config('sisp.redirect_url'))}}" class="mt-10 inline-block text-white bg-green-500 px-4 py-2 rounded-xl hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 w-full transition-colors duration-200">{{ $name }}</a>
