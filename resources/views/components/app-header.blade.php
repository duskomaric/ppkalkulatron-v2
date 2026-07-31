<header class="sticky top-0 z-40 h-[56px] flex items-center bg-[var(--color-bg)]/20 backdrop-blur-lg border-b border-[var(--color-border)]">
    <div class="max-w-[1200px] w-full mx-auto px-4 sm:px-6 flex justify-between items-center gap-2">
        <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
            <a href="{{ route('invoices.index') }}"
               class="h-8 w-8 shrink-0 bg-primary rounded-xl flex items-center justify-center text-white shadow-glow-primary transition-all duration-500">
                <x-icon name="calculator" class="h-4 w-4" />
            </a>

            {{-- Naziv može biti dug: skraćuje se, i ustupa mjesto meniju na širokom ekranu. --}}
            @php($companyName = app(\App\Settings\CompanySettings::class)->name)

            @if ($companyName)
                <a href="{{ route('settings.company.edit') }}" title="{{ $companyName }}"
                   class="min-w-0 lg:max-w-[220px] xl:max-w-[320px] text-sm font-black tracking-tighter italic truncate text-[var(--color-text-main)] hover:text-primary transition-colors">
                    {{ $companyName }}
                </a>

                <span class="hidden lg:block h-4 w-px bg-[var(--color-border)] shrink-0"></span>
            @endif

            <nav class="hidden lg:flex items-center gap-1.5 shrink-0">
                @foreach ($navItems as $item)
                    <x-nav-link :href="$item['href']" :active="$item['active']" :icon="$item['icon']">{{ $item['title'] }}</x-nav-link>
                @endforeach
            </nav>
        </div>

        <div class="flex items-center gap-1 sm:gap-2 shrink-0">
            <button type="button" @click="settingsDrawer = true"
                    class="relative cursor-pointer h-8 w-8 rounded-lg border border-[var(--color-border)] flex items-center justify-center text-[var(--color-text-dim)] hover:text-primary hover:border-primary/30 transition-all">
                <x-icon name="cog" class="h-4 w-4" />
            </button>

            <button type="button" @click="userDrawer = true"
                    class="cursor-pointer h-9 w-9 bg-[var(--color-surface)] text-[var(--color-text-muted)] rounded-xl flex items-center justify-center font-bold text-xs border border-[var(--color-border)] hover:border-primary hover:text-primary transition-all">
                <x-icon name="user" class="h-5 w-5" />
            </button>
        </div>
    </div>
</header>
