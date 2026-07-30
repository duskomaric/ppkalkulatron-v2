<!DOCTYPE html>
<html lang="sr-Latn" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
    <meta name="theme-color" content="#0B0B0F">
    <title>Otključaj — ppKalkulatron</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans">
    <div class="fixed inset-0 overflow-hidden -z-10">
        <div class="glow-ball glow-ball-primary -top-20 -left-20"></div>
        <div class="glow-ball glow-ball-secondary -bottom-20 -right-20"></div>
    </div>

    <div class="min-h-screen flex items-center justify-center px-6">
        <div class="w-full max-w-xs text-center animate-fade-in">
            <span class="inline-flex h-14 w-14 bg-primary rounded-2xl items-center justify-center text-white shadow-glow-primary mb-5">
                <x-icon name="calculator" class="h-7 w-7" />
            </span>

            <h1 class="text-2xl font-black tracking-tight italic mb-1">ppKalkulatron</h1>
            <p class="text-sm text-[var(--color-text-dim)] mb-8">Unesite PIN</p>

            <form method="POST" action="{{ route('unlock.store') }}" class="space-y-4">
                @csrf

                <input name="pin" type="password" inputmode="numeric" autocomplete="off" autofocus maxlength="8"
                       placeholder="••••"
                       class="w-full px-4 py-4 bg-[var(--color-surface)] border {{ $errors->any() ? 'border-[var(--color-error)]' : 'border-[var(--color-border)]' }} rounded-2xl text-center text-2xl font-black tracking-[0.5em] outline-none focus:border-primary/50 focus:ring-4 focus:ring-primary/10 transition-all">

                @error('pin')
                    <p class="text-xs font-bold text-[var(--color-error)]">{{ $message }}</p>
                @enderror

                <x-button variant="primary" class="w-full !py-3.5">Otključaj</x-button>
            </form>
        </div>
    </div>
</body>
</html>
