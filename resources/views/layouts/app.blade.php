<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Layout</title>
    <style>body{font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;}</style>
    @stack('styles')
</head>
<body>
    <main>
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>

