<!DOCTYPE html>
<html lang="sr-Latn">
<head>
    <x-app-head :title="View::yieldContent('title')" />
</head>
<body class="font-sans">
<div class="min-h-screen flex flex-col pb-nav relative"
     x-data="{ userDrawer: false, settingsDrawer: false, moreDrawer: false, setupDrawer: false }"
     {{-- Nepodešena aplikacija sama otvara vodič, jednom po učitavanju stranice. --}}
     x-init="$nextTick(() => setupDrawer = @js($setupShouldShow))">
    <div class="fixed inset-0 overflow-hidden -z-10 pointer-events-none">
        <div class="glow-ball glow-ball-primary -top-20 -left-20"></div>
        <div class="glow-ball glow-ball-secondary -bottom-20 -right-20"></div>
    </div>

    <x-app-header />

    @php
        $helpAnchor = match (true) {
            request()->routeIs('invoices.*') => 'racuni',
            request()->routeIs('clients.*') => 'klijenti',
            request()->routeIs('articles.*') => 'artikli',
            request()->routeIs('bank-accounts.*') => 'bankovni-racuni',
            request()->routeIs('currencies.*') => 'valute',
            request()->routeIs('settings.company.*', 'profile.*') => 'profil-kompanije',
            request()->routeIs('settings.fiscal.*') => 'fiskalizacija',
            request()->routeIs('settings.mail.*') => 'mail',
            request()->routeIs('settings.backup.*') => 'backup',
            request()->routeIs('settings.database.*') => 'backup-aplikacije',
            request()->routeIs('settings.diagnostics.*') => 'dijagnostika',
            request()->routeIs('settings.general.*') => 'numeracija',
            request()->routeIs('settings.menu.*') => 'meni',
            request()->routeIs('settings.pin.*', 'unlock.*') => 'pin',
            default => null,
        };
    @endphp

    <main class="grow max-w-[1200px] w-full mx-auto px-5 py-6 relative">
        <div class="flex items-center justify-between mb-6 gap-3">
            <h1 class="flex items-center gap-1.5 text-2xl font-black tracking-tight italic">@yield('heading', View::yieldContent('title'))</h1>
            <div class="flex items-center gap-2">
                @if ($helpAnchor)
                    <a href="{{ route('help') }}#{{ $helpAnchor }}" title="Pomoć za ovu stranicu"
                       class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-[var(--color-border)] text-[var(--color-text-dim)] transition-colors hover:border-[var(--color-primary)] hover:text-[var(--color-primary)]">
                        <x-icon name="info" class="h-5 w-5" />
                    </a>
                @endif

                @yield('actions')
            </div>
        </div>

        @yield('content')
    </main>

    <x-bottom-nav />
    <x-user-drawer />
    <x-module-drawer />
    <x-settings-drawer />
    <x-setup-drawer />
    <x-toast />
    <x-flash />
    <x-confirm-modal />
</div>

@if ($autoLockMinutes > 0)
    <script>
        (() => {
            const limit = {{ $autoLockMinutes }} * 60 * 1000;
            let hiddenAt = null;

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    hiddenAt = Date.now();

                    return;
                }

                // Zaključan telefon ne šalje zahtjeve, pa server ne zna da je vrijeme isteklo.
                if (hiddenAt && Date.now() - hiddenAt >= limit) {
                    window.location.reload();
                }

                hiddenAt = null;
            });
        })();
    </script>
@endif

@stack('scripts')
</body>
</html>
