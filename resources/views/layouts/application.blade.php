<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'Northstar Goods') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=fraunces:600,700|plus-jakarta-sans:400,500,600,700,800|space-grotesk:400,500,700" rel="stylesheet" />

        @if ($mountVueApp ?? true)
            <script>
                window.__APP_CONTEXT__ = @json($context ?? ['app' => 'frontend']);
            </script>

            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            @vite(['resources/css/app.css'])
        @endif
    </head>
    <body class="antialiased" data-app="{{ $context['app'] ?? 'frontend' }}">
        @if (session('status'))
            <div class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
                <div class="glass-panel rounded-3xl border border-emerald-300/30 bg-emerald-500/10 px-5 py-4 text-sm text-emerald-100">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        @yield('content')
    </body>
</html>
