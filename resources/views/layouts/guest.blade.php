<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Theme Init -->
        <script>
            document.documentElement.classList.toggle('dark', (() => {
                const saved = localStorage.getItem('theme');
                if (saved) return saved === 'dark';
                return window.matchMedia('(prefers-color-scheme: dark)').matches;
            })());
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-white dark:bg-[#0B1120] dark:text-slate-100">
        <div class="min-h-screen">
            {{ $slot }}
        </div>
    </body>
</html>
