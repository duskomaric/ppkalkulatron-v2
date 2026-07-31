<!DOCTYPE html>
<html lang="sr-Latn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
    <meta name="theme-color" content="#0B0B0F">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ppKalkulatron')</title>
    {{-- Tema se primjenjuje prije iscrtavanja, inače tamna bljesne pri svijetloj temi. --}}
    <script>
        (() => {
            const choice = localStorage.getItem('theme') || 'dark';
            const dark = choice === 'system'
                ? window.matchMedia('(prefers-color-scheme: dark)').matches
                : choice === 'dark';
            document.documentElement.classList.toggle('dark', dark);
            document.documentElement.classList.toggle('light', ! dark);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans">
<div class="min-h-screen flex flex-col pb-32 lg:pb-8 relative" x-data="{ userDrawer: false, settingsDrawer: false }">
    <div class="fixed inset-0 overflow-hidden -z-10 pointer-events-none">
        <div class="glow-ball glow-ball-primary -top-20 -left-20"></div>
        <div class="glow-ball glow-ball-secondary -bottom-20 -right-20"></div>
    </div>

    <x-app-header />

    <main class="grow max-w-[1200px] w-full mx-auto px-5 py-6 relative">
        <div class="flex items-center justify-between mb-6 gap-3">
            <h1 class="text-2xl font-black tracking-tight italic">@yield('heading', View::yieldContent('title'))</h1>
            @yield('actions')
        </div>

        <x-flash />

        @yield('content')
    </main>

    <x-bottom-nav />
    <x-user-drawer />
    <x-settings-drawer />
    <x-toast />
</div>

@stack('scripts')
</body>
</html>
