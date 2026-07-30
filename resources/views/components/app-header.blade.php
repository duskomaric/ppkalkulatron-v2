@php($pinEnabled = app(\App\Services\PinLock::class)->isEnabled())

<header class="sticky top-0 z-40 h-14 flex items-center bg-[var(--color-bg)]/20 backdrop-blur-lg border-b border-[var(--color-border)]">
    <div class="max-w-[1200px] w-full mx-auto px-4 sm:px-6 flex justify-between items-center gap-2">
        <a href="{{ route('invoices.index') }}" class="flex items-center gap-3 min-w-0">
            <span class="h-8 w-8 shrink-0 bg-primary rounded-xl flex items-center justify-center text-white shadow-glow-primary">
                <x-icon name="calculator" class="h-4 w-4" />
            </span>
            <span class="text-sm font-black tracking-tight italic hidden sm:block">ppKalkulatron</span>
        </a>

        <nav class="hidden lg:flex items-center gap-1.5">
            <x-nav-link :href="route('invoices.index')" :active="request()->routeIs('invoices.*')" icon="file-text">Računi</x-nav-link>
        </nav>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('settings.pin.edit') }}"
               class="h-8 w-8 rounded-lg border border-[var(--color-border)] flex items-center justify-center text-[var(--color-text-dim)] hover:text-primary hover:border-primary/30 transition-all"
               title="Podešavanja">
                <x-icon name="cog" class="h-4 w-4" />
            </a>

            @if ($pinEnabled)
                <form method="POST" action="{{ route('unlock.destroy') }}">
                    @csrf
                    <button type="submit" title="Zaključaj"
                            class="h-8 w-8 rounded-lg border border-[var(--color-border)] flex items-center justify-center text-[var(--color-text-dim)] hover:text-primary hover:border-primary/30 transition-all cursor-pointer">
                        <x-icon name="lock" class="h-4 w-4" />
                    </button>
                </form>
            @endif
        </div>
    </div>
</header>
