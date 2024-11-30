@props(['name'])

<a href="{{url(config('sisp.redirect_url'))}}" class="mt-10 inline-block text-white bg-green-500 px-4 py-2 rounded-xl hover:bg-green-600 w-full">{{ $name }}</a>
