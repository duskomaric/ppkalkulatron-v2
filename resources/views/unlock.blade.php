<!DOCTYPE html>
<html lang="sr-Latn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
    <meta name="theme-color" content="#0B0B0F">
    <title>Otključaj — ppKalkulatron</title>

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
<body class="min-h-screen font-sans">
    <div class="fixed inset-0 overflow-hidden -z-10">
        <div class="glow-ball glow-ball-primary -top-20 -left-20"></div>
        <div class="glow-ball glow-ball-secondary -bottom-20 -right-20"></div>
    </div>

    <div class="min-h-screen flex items-center justify-center px-6">
        <div class="w-full max-w-sm text-center animate-fade-in">
            <span class="inline-flex h-14 w-14 bg-primary rounded-2xl items-center justify-center text-white shadow-glow-primary mb-5">
                <x-icon name="calculator" class="h-7 w-7" />
            </span>

            <h1 class="text-2xl font-black tracking-tight italic mb-1">ppKalkulatron</h1>
            <p class="text-sm text-[var(--color-text-dim)] mb-8">Unesite PIN</p>

            {{--
                Četiri odvojena polja, kao kod dvofaktorske prijave: kucanje pomjera
                fokus naprijed, brisanje nazad, a kad su sve četiri cifre unesene forma
                se šalje sama. Jedno polje po cifri je i jedini način da kursor stoji
                tamo gdje se piše — u jednom polju sa razmakom slova stajao je na sredini.
            --}}
            <form method="POST" action="{{ route('unlock.store') }}" x-data="pinEntry()">
                @csrf
                <input type="hidden" name="pin" :value="digits.join('')">

                <div class="flex justify-center gap-3" x-on:paste.prevent="paste($event)">
                    <template x-for="index in 4" :key="index">
                        <input type="password" inputmode="numeric" autocomplete="one-time-code" maxlength="1"
                               :value="digits[index - 1]"
                               x-ref="box"
                               x-on:input="type(index - 1, $event)"
                               x-on:keydown="key(index - 1, $event)"
                               x-on:focus="$event.target.select()"
                               aria-label="Cifra PIN-a"
                               @class([
                                   'h-16 w-14 text-center text-2xl font-black bg-[var(--color-surface)] border-2 rounded-2xl outline-none transition-all caret-primary focus:border-primary focus:ring-4 focus:ring-primary/10',
                                   'border-[var(--color-error)]' => $errors->any(),
                                   'border-[var(--color-border)]' => ! $errors->any(),
                               ])>
                    </template>
                </div>

                @error('pin')
                    <p class="text-xs font-bold text-[var(--color-error)] mt-4">{{ $message }}</p>
                @enderror

                @if (session('error'))
                    <p class="text-xs font-bold text-[var(--color-error)] mt-4">{{ session('error') }}</p>
                @endif

                {{-- Dugme je za pristupačnost i za slučaj da automatsko slanje izostane. --}}
                <button type="submit" x-show="filled()" x-cloak
                        class="w-full mt-6 py-3.5 bg-primary text-white rounded-xl font-black text-[11px] uppercase tracking-[0.2em] shadow-glow-primary hover:scale-[1.02] active:scale-95 transition-all cursor-pointer">
                    Otključaj
                </button>
            </form>
        </div>
    </div>
</body>
</html>
