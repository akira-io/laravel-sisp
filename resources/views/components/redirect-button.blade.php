@props(['name', 'variant' => 'success'])

@php
    $colors = match($variant) {
        'primary' => 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
        'danger' => 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
        'neutral' => 'bg-gray-600 hover:bg-gray-700 focus:ring-gray-500',
        default => 'bg-green-500 hover:bg-green-600 focus:ring-green-500',
    };
@endphp

<a href="{{url(config('sisp.redirect_url'))}}" role="button" {{ $attributes->merge(['class' => "mt-10 inline-block text-white $colors px-4 py-2 rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 w-full transition-colors duration-200 text-center decoration-0"]) }}>{{ $name }}</a>
