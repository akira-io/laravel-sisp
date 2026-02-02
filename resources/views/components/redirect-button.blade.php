@props(['name', 'variant' => 'success'])

@php
    $variants = [
        'success' => 'bg-green-500 hover:bg-green-600 focus:ring-green-500',
        'neutral' => 'bg-zinc-500 hover:bg-zinc-600 focus:ring-zinc-500',
        'primary' => 'bg-blue-500 hover:bg-blue-600 focus:ring-blue-500',
    ];

    $classes = $variants[$variant] ?? $variants['success'];
@endphp

<a href="{{url(config('sisp.redirect_url'))}}" {{ $attributes->merge(['class' => "mt-10 inline-block text-white px-4 py-2 rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-zinc-900 w-full transition-colors duration-200 $classes"]) }}>{{ $name }}</a>
