@props(['name', 'variant' => 'success'])

@php
    $variants = [
        'success' => 'bg-green-500 hover:bg-green-600 focus:ring-green-500',
        'neutral' => 'bg-gray-600 hover:bg-gray-700 focus:ring-gray-500',
        'primary' => 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
    ];
    $variantClasses = $variants[$variant] ?? $variants['success'];
@endphp

<a href="{{url(config('sisp.redirect_url'))}}" class="mt-10 inline-block text-white {{ $variantClasses }} px-4 py-2 rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-zinc-950 w-full transition-colors duration-200">{{ $name }}</a>
