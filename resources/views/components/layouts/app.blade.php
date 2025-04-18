<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class=''>
<head>
	<meta charset="utf-8">
	<meta name="application-name" content="{{ config('app.name') }}">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>{{ config('app.name') }}</title>
	@vite('resources/css/app.css')
	<script>
      if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
          document.documentElement.classList.add('dark');
      }
	</script>
</head>
<body class="font-sans antialiased dark:bg-zinc-950 min-h-screen bg-white/50">
{{ $slot }}

{{--@vite('resources/js/app.js')--}}
</body>
</html>
